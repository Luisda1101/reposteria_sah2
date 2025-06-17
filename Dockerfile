# Imagen oficial de PHP con servidor embebido
FROM php:8.1.31-cli

# Instala extensiones necesarias (modifica si usas otras)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia el código al contenedor
COPY . /app
WORKDIR /app

# Puerto de Render
EXPOSE 10000

# Comando para iniciar el servidor PHP en Render
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
