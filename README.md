# **ESTRUCTURA MVC MINDEF V. 1.0**
## REQUERIMIENTOS

 - PHP V7.2.4 o superior
 - Extensión PDO_INFORMIX
 - NODE JS V17.9.0
 - NPM V8.5
 - COMPOSER V2.3 o superior
 - GIT V2.35 o superior.
 - MOD_REWRITE activo en el servidor
___

## PASOS PARA INICIAR 
###  1. Verificar MOD_REWRITE
El servidor deberá poseer al menos esta configuración 

```conf

<Directory /var/www/html>
	AllowOverride All
</Directory>

```
En un servidor Ubuntu esta configuración debe colocarse en ***/etc/apache2/sites-available/*** 

###  2. Clonar repositorio
Clonarlo en la carpeta que se este utilizando como base en el servidor (Ej. C:\docker)

###  3. Crear archivo GIT IGNORE (.gitignore) 
Debe colocarse en la raíz del proyecto, con el siguiente  contenido

```git
node_modules
vendor
composer.lock
packagelock.json
public/
build
.gitignore
.htaccess
public/.htaccess
temp
storage
includes/.env
```
###  4. Crear archivos HTACCESS
Estos archivos se usaran para redirigir las consultas hacia el archivo **_index.php_**

#### Archivo htaccess de la raíz
Deberá colocarse en la raíz del proyecto

```
RewriteEngine on
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```
#### Archivo htaccess de la carpeta public 
Deberá colocarse dentro de la carpeta public

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

###  5. Crear archivo .env
Este archivo deberá contener la información según el entorno en que se ejecute el proyecto y deberá contener esta información

```
DEBUG_MODE = 0
DB_HOST=host
DB_SERVICE=port
DB_SERVER=server_name
DB_NAME=db_name

APP_NAME = "app_name"
```

###  6. Instalar paquetes de node
Ejecutar en consola el comando siguiente y esperar a que termine su ejecución 
```
npm  install
```

###  7. Instalar paquetes de composer
Ejecutar en consola el comando siguiente y esperar a que termine su ejecución 
```
composer  install
```

###  8. Construir archivos en la carpeta pública
Ejecutar en consola el comando siguiente y esperar a que termine su ejecución 
```
npm run build
```
Este comando permanecerá en ejecución  mientras se este trabajando en el proyecto

###  9. Configurar versiones y descripción del proyecto

Configurar los archivos con la información del proyecto y la versión en la que se esta trabajando

 - package.json
 - composer.json