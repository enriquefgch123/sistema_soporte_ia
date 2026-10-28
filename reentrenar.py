import mysql.connector
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
import joblib

def reentrenar_modelo():
    print("Iniciando proceso de reentrenamiento optimizado...")

    # 1. Conexión a la base de datos MySQL
    conexion = mysql.connector.connect(
        host="localhost",
        user="root",
        password="", # Coloca tu contraseña de MySQL si la tienes configurada
        database="sistema_soporte_ia"
    )

    # 2. Consultar solo los registros validados por el analista
    query = """
        SELECT descripcion_texto, categoria_final 
        FROM incidencias 
        WHERE estado = 'Validado' AND categoria_final IS NOT NULL
    """
    df = pd.read_sql(query, conexion)
    conexion.close()

    if len(df) < 2:
        print("Advertencia: No hay suficientes tickets validados para reentrenar.")
        return

    print(f"Reentrenando con {len(df)} registros validados...")

    # 3. Preparar los datos
    X = df['descripcion_texto']
    y = df['categoria_final']

    # 4. Vectorizador corregido (sin stop_words en string)
    vectorizador = TfidfVectorizer(ngram_range=(1, 2))
    X_vec = vectorizador.fit_transform(X)

    # 5. Usar Regresión Logística con balance de clases para evitar sesgo a Hardware
    modelo_categoria = LogisticRegression(max_iter=1000, class_weight='balanced')
    modelo_categoria.fit(X_vec, y)

    # 6. Guardar los nuevos archivos .pkl
    joblib.dump(modelo_categoria, 'modelo_categoria.pkl')
    joblib.dump(vectorizador, 'vectorizador.pkl')

    print("Modelo reentrenado con exito. Sesgo corregido.")

if __name__ == "__main__":
    reentrenar_modelo()