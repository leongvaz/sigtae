import pyodbc

try:
    conexion = pyodbc.connect('DRIVER={ODBC Driver 18 for SQL Server}; SERVER=10.4.22.9; DATABASE=scintegral;UID=pas_consulta;PWD=Consult@01')
    print("¡Conexión exitosa!")
    conexion.close()
except Exception as e:
    print("Error de conexión:", e)
