# Talent Flow

Sistema web para la gestión de procesos de selección, candidatos y postulaciones.

Talent Flow permite centralizar la gestión de posiciones laborales, candidatos y postulaciones, organizando cada proceso de selección mediante etapas y entrevistas.

El proyecto fue desarrollado con **Laravel y PostgreSQL**, aplicando una arquitectura orientada a separar responsabilidades entre las diferentes capas de la aplicación.

---

## 🚀 Características principales

* Gestión de posiciones laborales.
* Gestión de candidatos.
* Registro y seguimiento de postulaciones.
* Gestión de etapas dentro del proceso de selección.
* Gestión de entrevistas.
* Administración de habilidades.
* Relación entre candidatos y habilidades.
* Seguimiento del estado de las postulaciones.
* API REST para las operaciones principales.
* Persistencia de información mediante PostgreSQL.

---

## 🛠️ Tecnologías utilizadas

### Backend

* PHP
* Laravel
* REST API

### Base de datos

* PostgreSQL

### Herramientas

* Git
* Composer
* Postman

---

## 🏗️ Arquitectura

El proyecto utiliza una arquitectura basada en responsabilidades separadas para facilitar el mantenimiento y evolución del sistema.

```text
Cliente
   │
   ▼
Routes
   │
   ▼
Controllers
   │
   ▼
Services
   │
   ▼
Models / Eloquent
   │
   ▼
PostgreSQL
```

El flujo general de una operación es:

```text
Request
   ↓
Route
   ↓
Controller
   ↓
Service
   ↓
Model
   ↓
Database
   ↓
Response
```

---

## 📂 Estructura del proyecto

```text
talent-flow/
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │
│   ├── Models/
│   │
│   └── Services/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── routes/
│   └── api.php
│
├── tests/
│
├── postman/
│
├── .env.example
├── composer.json
└── README.md
```

---

## 📋 Módulos principales

### Positions

Permite administrar las posiciones laborales disponibles dentro del sistema.

### Candidates

Permite registrar y administrar la información de los candidatos.

### Applications

Relaciona candidatos con posiciones y permite realizar el seguimiento de cada postulación.

### Stages

Permite representar las diferentes etapas de un proceso de selección.

### Interviews

Permite registrar entrevistas asociadas al proceso de selección.

### Skills

Permite administrar las habilidades de los candidatos y sus relaciones.

---

## 🗄️ Modelo de datos

El sistema utiliza PostgreSQL como motor de base de datos.

Las principales entidades del sistema son:

```text
Position
   │
   └── Applications
          │
          └── Candidate
                 │
                 └── Skills

Application
   │
   └── Stages
          │
          └── Interviews
```

Las relaciones entre las entidades permiten representar el flujo completo de una postulación dentro del proceso de selección.

---

## ⚙️ Configuración y ejecución local

### Requisitos

* PHP 8.x
* Composer
* PostgreSQL
* Git
* Node.js + npm (si se requieren assets)

### 1. Clonar el repositorio

```bash
git clone https://github.com/JonathanEme/talent-flow.git

cd talent-flow
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar el entorno

Copiar el archivo `.env.example`:

```bash
cp .env.example .env
```

Configurar las variables de conexión a PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=talent_flow
DB_USERNAME=postgres
DB_PASSWORD=
```

### 4. Generar la clave de aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar migraciones

```bash
php artisan migrate
```

Si el proyecto dispone de datos iniciales:

```bash
php artisan db:seed
```

### 6. Ejecutar el servidor

```bash
php artisan serve
```

La aplicación estará disponible en:

```text
http://127.0.0.1:8000
```

---

## 🔌 API

Talent Flow expone endpoints REST para interactuar con los principales recursos del sistema.

Ejemplo de recursos:

```text
/api/positions
/api/candidates
/api/applications
/api/stages
/api/interviews
/api/skills
```

Las respuestas de la API utilizan formato JSON.

### Ejemplo

```json
{
    "success": true,
    "data": []
}
```

---

## 📮 Postman

Las peticiones de la API pueden probarse utilizando Postman.

Si el repositorio incluye una colección, puede encontrarse dentro de:

```text
postman/
```

Configurar la URL base de la API:

```text
http://127.0.0.1:8000
```

---

## 🧪 Testing

El proyecto puede utilizar la suite de testing de Laravel para validar el comportamiento de sus diferentes componentes.

Los tests se encuentran dentro de:

```text
tests/
```

Para ejecutarlos:

```bash
php artisan test
```

---

## ⚠️ Problemas comunes

### Error de conexión con PostgreSQL

Verificar:

* que PostgreSQL esté ejecutándose;
* nombre de la base de datos;
* usuario;
* contraseña;
* puerto configurado en `.env`.

### Error de clave de aplicación

Ejecutar:

```bash
php artisan key:generate
```

### Error de dependencias

Eliminar las dependencias e instalar nuevamente:

```bash
rm -rf vendor
composer install
```

---

## 🔮 Próximas mejoras

Algunas funcionalidades que pueden incorporarse en futuras versiones:

* Autenticación y autorización de usuarios.
* Dashboard para seguimiento de procesos.
* Notificaciones.
* Filtros avanzados de candidatos.
* Gestión de estados mediante workflows.
* Métricas y reportes de procesos de selección.
* Integración con servicios externos.
* Documentación automática de la API mediante OpenAPI/Swagger.

---

## 👨‍💻 Autor

**Jonathan Mendola**

Backend Developer especializado en PHP y Laravel.

* GitHub: https://github.com/JonathanEme
* Portfolio: https://jonathaneme.github.io/
