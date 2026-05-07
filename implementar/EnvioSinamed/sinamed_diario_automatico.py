#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Automatización diaria SINAMED + SIGAMI (ventana calcom).

Flujo previsto (ejecutar ~04:00):
  1) Prep: medidores con fecha de envío en calcom = HOY+2 → UPDATE tlpnIdSigAmi 4→6
  2) Envío: medidores con fecha HOY → lecturas PostgreSQL + POST callback SINAMED
  3) Revertir: medidores con fecha HOY-2 → UPDATE tlpnIdSigAmi 6→4

Dependencias:
  pip install pyodbc psycopg2-binary requests

Configuración:
  Copie config_sinamed_automatizado.example.json → config_sinamed_automatizado.local.json
  Opcional: variables de entorno SINAMED_TOKEN, SQLSERVER_PWD, POSTGRES_PASSWORD sobrescriben el JSON.

Programador de tareas (Windows): ejecutar desde esta carpeta:
  python sinamed_diario_automatico.py --config config_sinamed_automatizado.local.json
"""

from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any, Dict, List, Mapping, Optional, Sequence, Tuple

try:
    import requests
except ImportError:
    requests = None  # type: ignore

try:
    import psycopg2
    import psycopg2.extras
except ImportError:
    psycopg2 = None  # type: ignore

try:
    import pyodbc
except ImportError:
    pyodbc = None  # type: ignore


SCRIPT_DIR = Path(__file__).resolve().parent


def _today_str(d: date) -> str:
    return d.isoformat()


def load_json(path: Path) -> Any:
    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def save_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    with tmp.open("w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    tmp.replace(path)


def ciclo_from_cuenta(cuenta: str) -> Optional[str]:
    s = (cuenta or "").strip()
    if len(s) < 2:
        return None
    part = s[:2].lstrip("0")
    return part if part else "0"


def build_fecha_to_ciclos(calcom: Sequence[Mapping[str, Any]]) -> Dict[str, List[str]]:
    out: Dict[str, List[str]] = {}
    for row in calcom:
        ciclo = str(row.get("ciclo", "")).strip()
        fecha = str(row.get("fecha", "")).strip()
        if not ciclo or not fecha:
            continue
        out.setdefault(fecha, []).append(ciclo)
    return out


def meters_for_send_date(
    completo: Sequence[Mapping[str, Any]],
    fecha_to_ciclos: Mapping[str, Sequence[str]],
    send_date: date,
) -> List[Dict[str, str]]:
    key = _today_str(send_date)
    ciclos_hoy = set(str(c).strip() for c in fecha_to_ciclos.get(key, []))
    if not ciclos_hoy:
        return []
    seen = set()
    items: List[Dict[str, str]] = []
    for row in completo:
        cuenta = str(row.get("Cuenta") or row.get("cuenta") or "").strip()
        medidor = str(row.get("Número CFE") or row.get("numero_cfe") or "").strip()
        if not medidor:
            continue
        ciclo = ciclo_from_cuenta(cuenta)
        if ciclo is None or ciclo not in ciclos_hoy:
            continue
        k = medidor.upper()
        if k in seen:
            continue
        seen.add(k)
        items.append({"medidor": k, "cuenta": cuenta, "ciclo": ciclo})
    items.sort(key=lambda x: x["medidor"])
    return items


def build_universe(completo: Sequence[Mapping[str, Any]]) -> List[Dict[str, str]]:
    seen = set()
    out: List[Dict[str, str]] = []
    for row in completo:
        cuenta = str(row.get("Cuenta") or row.get("cuenta") or "").strip()
        medidor = str(row.get("Número CFE") or row.get("numero_cfe") or "").strip()
        if not medidor:
            continue
        ciclo = ciclo_from_cuenta(cuenta) or ""
        k = medidor.upper()
        if k in seen:
            continue
        seen.add(k)
        out.append({"medidor": k, "cuenta": cuenta, "ciclo": ciclo})
    out.sort(key=lambda x: x["medidor"])
    return out


def meters_sql_in_list(meters: Sequence[str]) -> str:
    cleaned = []
    for m in meters:
        u = "".join(ch for ch in str(m).upper() if ch.isalnum())
        if u:
            cleaned.append(u)
    return ",".join("'" + x.replace("'", "") + "'" for x in cleaned)


def sql_server_update_sigami(
    cfg: Mapping[str, Any],
    medidores: Sequence[str],
    nuevo_estado: int,
    dry_run: bool,
) -> Tuple[int, Optional[str]]:
    if not medidores:
        return 0, None
    if dry_run:
        return 0, None
    if pyodbc is None:
        return 0, "pyodbc no instalado; omitiendo UPDATE SQL Server."
    sqlcfg = cfg.get("sql_server") or {}
    driver = os.environ.get("SQLSERVER_DRIVER") or sqlcfg.get("driver") or "{ODBC Driver 17 for SQL Server}"
    server = os.environ.get("SQLSERVER_SERVER") or sqlcfg.get("server")
    database = os.environ.get("SQLSERVER_DATABASE") or sqlcfg.get("database")
    uid = os.environ.get("SQLSERVER_UID") or sqlcfg.get("uid")
    pwd = os.environ.get("SQLSERVER_PWD") or sqlcfg.get("pwd")
    if not all([server, database, uid, pwd]):
        return 0, "Faltan parámetros sql_server (server/database/uid/pwd)."
    conn_s = (
        f"DRIVER={driver};SERVER={server};DATABASE={database};UID={uid};PWD={pwd};"
        "TrustServerCertificate=yes;"
    )
    total = 0
    try:
        cnxn = pyodbc.connect(conn_s, timeout=30)
        try:
            chunk_size = int(sqlcfg.get("chunk_size") or 1000)
            for i in range(0, len(medidores), chunk_size):
                chunk = list(medidores[i : i + chunk_size])
                placeholders = ",".join("?" * len(chunk))
                q = (
                    f"UPDATE [kcentinel].[dbo].[TELEPNUEVOMEDIDOR] SET [tlpnIdSigAmi] = ? "
                    f"WHERE [tlpnMedidor] IN ({placeholders})"
                )
                cur = cnxn.cursor()
                cur.execute(q, [nuevo_estado] + chunk)
                # rows affected puede ser -1 según driver
                ra = cur.rowcount if cur.rowcount is not None and cur.rowcount >= 0 else len(chunk)
                total += ra
                cnxn.commit()
        finally:
            cnxn.close()
    except Exception as exc:  # noqa: BLE001
        return total, str(exc)
    return total, None


def postgres_envio_rows(cfg: Mapping[str, Any], medidores: Sequence[str]) -> Tuple[List[Dict[str, Any]], Optional[str]]:
    if psycopg2 is None:
        return [], "psycopg2 no instalado."
    pgcfg = cfg.get("postgres") or {}
    envio = cfg.get("postgres_envio") or {}
    sql_path_name = envio.get("sql_file") or "envio_sinamed.sql"
    sql_path = (SCRIPT_DIR / str(sql_path_name)).resolve()
    if not sql_path.is_file():
        return [], f"No existe el archivo SQL de envío: {sql_path}"
    sql_text = sql_path.read_text(encoding=envio.get("encoding") or "utf-8")
    med_in = meters_sql_in_list(medidores)
    if "%%MEDIDORES_IN%%" not in sql_text:
        return [], "El SQL debe contener el marcador %%MEDIDORES_IN%%."
    sql_exec = sql_text.replace("%%MEDIDORES_IN%%", med_in)
    host = os.environ.get("POSTGRES_HOST") or pgcfg.get("host")
    port = int(os.environ.get("POSTGRES_PORT") or pgcfg.get("port") or 5432)
    dbname = os.environ.get("POSTGRES_DB") or pgcfg.get("database")
    user = os.environ.get("POSTGRES_USER") or pgcfg.get("user")
    password = os.environ.get("POSTGRES_PASSWORD") or pgcfg.get("password")
    if not all([host, dbname, user, password]):
        return [], "Faltan parámetros postgres en configuración."
    try:
        conn = psycopg2.connect(
            host=host,
            port=port,
            dbname=dbname,
            user=user,
            password=password,
            connect_timeout=30,
        )
        try:
            with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
                cur.execute(sql_exec)
                rows = cur.fetchall()
                lower_rows = []
                for r in rows:
                    lower_rows.append({str(k).lower(): v for k, v in dict(r).items()})
        finally:
            conn.close()
    except Exception as exc:  # noqa: BLE001
        return [], str(exc)
    return lower_rows, None


def map_rows_to_payload(
    rows: Sequence[Mapping[str, Any]],
    items_from_columns: Optional[Mapping[str, str]],
) -> List[Dict[str, Any]]:
    if not items_from_columns:
        return [dict(r) for r in rows]
    out: List[Dict[str, Any]] = []
    for r in rows:
        item: Dict[str, Any] = {}
        for out_key, src_col in items_from_columns.items():
            lk = str(src_col).lower()
            item[out_key] = r.get(lk)
        out.append(item)
    return out


def post_sinamed(cfg: Mapping[str, Any], payload_obj: Any, dry_run: bool) -> Tuple[int, Any, Optional[str]]:
    if dry_run:
        return 0, None, None
    if requests is None:
        return 0, None, "requests no instalado."
    sin = cfg.get("sinamed") or {}
    url = sin.get("callback_url") or ""
    token = os.environ.get("SINAMED_TOKEN") or sin.get("token") or ""
    if not url:
        return 0, None, "sinamed.callback_url vacío."
    headers = dict(sin.get("headers") or {})
    h_name = str(sin.get("token_header_name") or "TOKEN")
    if token and h_name:
        headers.setdefault(h_name, token)
    headers.setdefault("Content-Type", "application/json")
    timeout = float(sin.get("timeout_sec") or 120)
    try:
        r = requests.post(url, json=payload_obj, headers=headers, timeout=timeout)
        body_preview: Any
        try:
            body_preview = r.json()
        except Exception:  # noqa: BLE001
            body_preview = (r.text or "")[:4000]
        if not r.ok:
            return r.status_code, body_preview, f"HTTP {r.status_code}"
        return r.status_code, body_preview, None
    except Exception as exc:  # noqa: BLE001
        return 0, None, str(exc)


def append_enviado_txt(path: Path, block: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("a", encoding="utf-8") as f:
        f.write(block)
        if not block.endswith("\n"):
            f.write("\n")


def main(argv: Optional[Sequence[str]] = None) -> int:
    parser = argparse.ArgumentParser(description="SINAMED diario + cambios SIGAMI (calcom).")
    parser.add_argument(
        "--config",
        default=str(SCRIPT_DIR / "config_sinamed_automatizado.local.json"),
        help="Ruta al JSON de configuración.",
    )
    parser.add_argument("--dry-run", action="store_true", help="No ejecuta UPDATE ni POST; sí escribe snapshot.")
    parser.add_argument("--solo-prep", action="store_true")
    parser.add_argument("--solo-envio", action="store_true")
    parser.add_argument("--solo-revert", action="store_true")
    args = parser.parse_args(argv)

    cfg_path = Path(args.config).expanduser()
    if not cfg_path.is_file():
        print(f"No se encuentra configuración: {cfg_path}", file=sys.stderr)
        return 2

    cfg = load_json(cfg_path)
    dry_run = bool(args.dry_run or cfg.get("dry_run"))

    calcom_path = (SCRIPT_DIR / str(cfg.get("calcom_json") or "calcom.json")).resolve()
    completo_path = (SCRIPT_DIR / str(cfg.get("calcom_completo_json") or "calcomCompleto.json")).resolve()
    snapshot_path = (SCRIPT_DIR / str(cfg.get("snapshot_json") or "../../storage/json/sinamed_automation/snapshot.json")).resolve()
    history_path = (SCRIPT_DIR / str(cfg.get("history_jsonl") or "../../storage/json/sinamed_automation/run_history.jsonl")).resolve()
    enviado_path = (SCRIPT_DIR / str(cfg.get("enviado_txt") or "Enviado.txt")).resolve()
    universe_path = snapshot_path.parent / "universe_sinamed.json"

    calcom = load_json(calcom_path)
    completo = load_json(completo_path)
    fecha_to_ciclos = build_fecha_to_ciclos(calcom)

    state_aldesa = int(cfg.get("sigami_aldesa") or 4)
    state_sinamed = int(cfg.get("sigami_sinamed") or 6)

    today = date.today()
    d_prep = today + timedelta(days=2)
    d_send = today
    d_revert = today - timedelta(days=2)

    run_all = not (args.solo_prep or args.solo_envio or args.solo_revert)

    prep_items = meters_for_send_date(completo, fecha_to_ciclos, d_prep) if (run_all or args.solo_prep) else []
    send_items = meters_for_send_date(completo, fecha_to_ciclos, d_send) if (run_all or args.solo_envio) else []
    revert_items = meters_for_send_date(completo, fecha_to_ciclos, d_revert) if (run_all or args.solo_revert) else []

    prep_meters = [x["medidor"] for x in prep_items]
    send_meters = [x["medidor"] for x in send_items]
    revert_meters = [x["medidor"] for x in revert_items]

    prep_updated, prep_err = (0, None)
    if prep_meters and (run_all or args.solo_prep):
        prep_updated, prep_err = sql_server_update_sigami(cfg, prep_meters, state_sinamed, dry_run)

    revert_updated, revert_err = (0, None)
    if revert_meters and (run_all or args.solo_revert):
        revert_updated, revert_err = sql_server_update_sigami(cfg, revert_meters, state_aldesa, dry_run)

    send_details: List[Dict[str, Any]] = []
    post_status = None
    post_body = None
    post_err = None

    if send_meters and (run_all or args.solo_envio):
        if dry_run:
            for it in send_items:
                send_details.append(
                    {
                        "medidor": it["medidor"],
                        "cuenta": it["cuenta"],
                        "ciclo": it["ciclo"],
                        "delivered": None,
                        "received": None,
                        "ok": False,
                        "error": "dry_run: sin consulta PostgreSQL ni POST SINAMED",
                    }
                )
        else:
            rows, pg_err = postgres_envio_rows(cfg, send_meters)
            if pg_err:
                post_err = pg_err
                for it in send_items:
                    send_details.append(
                        {
                            "medidor": it["medidor"],
                            "cuenta": it["cuenta"],
                            "ciclo": it["ciclo"],
                            "delivered": None,
                            "received": None,
                            "ok": False,
                            "error": pg_err,
                        }
                    )
            elif not rows:
                post_err = "La consulta PostgreSQL no devolvió lecturas para los medidores programados hoy."
                for it in send_items:
                    send_details.append(
                        {
                            "medidor": it["medidor"],
                            "cuenta": it["cuenta"],
                            "ciclo": it["ciclo"],
                            "delivered": None,
                            "received": None,
                            "ok": False,
                            "error": post_err,
                        }
                    )
            else:
                payload_cfg = cfg.get("sinamed_payload") or {}
                wrapper_key = payload_cfg.get("wrapper_key") or "dataConst"
                items_map = payload_cfg.get("items_from_columns") or {
                    "nuMedidor": "numero_cfe",
                    "energiaActivaTotal": "delivered",
                }
                mapped = map_rows_to_payload(rows, items_map)

                row_by_meter = {}
                for r in mapped:
                    nu = r.get("nuMedidor") or r.get("numero_cfe") or r.get("medidor")
                    if nu:
                        row_by_meter[str(nu).upper().strip()] = r

                payload_obj: Any
                inner = mapped
                if wrapper_key:
                    payload_obj = {wrapper_key: inner}
                else:
                    payload_obj = inner

                post_status, post_body, post_err = post_sinamed(cfg, payload_obj, False)

                ok_global = post_err is None
                for it in send_items:
                    m = it["medidor"]
                    item = row_by_meter.get(m)
                    delivered = None
                    received = None
                    if item:
                        delivered = item.get("energiaActivaTotal")
                        if delivered is None:
                            delivered = item.get("delivered")
                        received = item.get("received")
                    err_local = None
                    if not ok_global:
                        err_local = post_err or "Fallo POST"
                    elif item is None:
                        err_local = "Sin lectura en PostgreSQL para este medidor"
                    send_details.append(
                        {
                            "medidor": m,
                            "cuenta": it["cuenta"],
                            "ciclo": it["ciclo"],
                            "delivered": delivered,
                            "received": received,
                            "ok": ok_global and item is not None,
                            "error": err_local,
                        }
                    )

    snapshot: Dict[str, Any] = {
        "generated_at": datetime.now().isoformat(timespec="seconds"),
        "run_date": _today_str(today),
        "dry_run": dry_run,
        "paths": {
            "calcom": str(calcom_path),
            "calcom_completo": str(completo_path),
            "snapshot": str(snapshot_path),
        },
        "prep_sigami_4_to_6": {
            "target_send_date": _today_str(d_prep),
            "sigami_target_state": state_sinamed,
            "count_calendar": len(prep_items),
            "sql_rows_reported": prep_updated,
            "sql_error": prep_err,
            "items": prep_items,
        },
        "send_sinamed_today": {
            "target_send_date": _today_str(d_send),
            "count_calendar": len(send_items),
            "post_http_status": post_status,
            "post_error": post_err,
            "post_response_preview": post_body,
            "details": send_details,
        },
        "revert_sigami_6_to_4": {
            "reference_send_date": _today_str(d_revert),
            "sigami_target_state": state_aldesa,
            "count_calendar": len(revert_items),
            "sql_rows_reported": revert_updated,
            "sql_error": revert_err,
            "items": revert_items,
        },
        "lists_for_ui": {
            "enviados_hoy_con_lectura": [d for d in send_details if d.get("ok")],
            "proximos_dos_dias_prep_sigami_6": prep_items,
            "cohorte_revertir_a_sigami_4_hoy": revert_items,
        },
        "universe": {"total": 0, "json_file": str(universe_path.name)},
    }

    uni = build_universe(completo)
    snapshot["universe"]["total"] = len(uni)
    save_json(universe_path, {"generated_at": snapshot["generated_at"], "items": uni})
    save_json(snapshot_path, snapshot)

    hist_line = {
        "run_date": snapshot["run_date"],
        "dry_run": dry_run,
        "prep_count": len(prep_items),
        "send_count": len(send_items),
        "revert_count": len(revert_items),
        "prep_sql_rows": prep_updated,
        "revert_sql_rows": revert_updated,
        "post_error": post_err,
    }
    history_path.parent.mkdir(parents=True, exist_ok=True)
    with history_path.open("a", encoding="utf-8") as f:
        f.write(json.dumps(hist_line, ensure_ascii=False) + "\n")

    # Bitácora tipo envioDiario Enviado.txt
    ok_ct = sum(1 for d in send_details if d.get("ok"))
    fail_ct = sum(1 for d in send_details if not d.get("ok"))
    lines = [
        "",
        f"Hora de inicio (automatizado): {snapshot['generated_at']}",
        f"Modo dry_run={dry_run}",
        f"Prep (+2d → SIGAMI {state_sinamed}): {len(prep_items)} medidores; sql_rows_reported={prep_updated}; err={prep_err}",
        f"Revert (-2d → SIGAMI {state_aldesa}): {len(revert_items)} medidores; sql_rows_reported={revert_updated}; err={revert_err}",
        f"Medidores enviados OK: {ok_ct}",
        f"Medidores con falla: {fail_ct}",
        "",
        "Lista de medidores enviados (detalle):",
    ]
    for d in send_details:
        lines.append(
            f"{d.get('medidor')} | delivered={d.get('delivered')} | received={d.get('received')} | ok={d.get('ok')} | err={d.get('error')}"
        )
    append_enviado_txt(enviado_path, "\n".join(lines))

    print(json.dumps(hist_line, ensure_ascii=False, indent=2))
    return 0 if not post_err and not prep_err and not revert_err else 1


if __name__ == "__main__":
    raise SystemExit(main())
