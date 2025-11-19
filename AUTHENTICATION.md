# 🔐 Guía de Autenticación - Sistema de Gestión de Turnos

## Tipos de Usuarios

El sistema maneja 3 tipos de roles:

1. **Administrador** (role_id: 1) - Gestión completa del sistema
2. **Agente** (role_id: 2) - Atención de turnos en sucursales
3. **Cliente** (role_id: 3) - Usuarios que solicitan turnos

---

## 📝 Registro Público (Solo Clientes)

**Endpoint:** `POST /api/register`

**Descripción:** Registro público desde la PWA. Crea automáticamente usuarios con rol de Cliente.

**Body (JSON):**
```json
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "123456"
}
```

**Respuesta exitosa (200):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 5,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "role_id": 3,
    "sucursal_id": null,
    "role": {
      "id": 3,
      "nombre": "Cliente"
    },
    "negocio": null
  }
}
```

---

## 🔑 Login

**Endpoint:** `POST /api/login`

**Body (JSON):**
```json
{
  "email": "admin@gestor.com",
  "password": "admin123"
}
```

**Respuesta exitosa (200):**
```json
{
  "token": "2|xyz789...",
  "user": {
    "id": 1,
    "name": "Administrador",
    "email": "admin@gestor.com",
    "role_id": 1,
    "sucursal_id": null,
    "role": {
      "id": 1,
      "nombre": "Administrador"
    },
    "negocio": null
  }
}
```

---

## 👥 Crear Usuarios Admin/Agente (Solo para Administradores)

**Endpoint:** `POST /api/users/create`

**Headers:**
```
Authorization: Bearer {token_del_admin}
Content-Type: application/json
```

**Body (JSON) - Crear Administrador:**
```json
{
  "name": "Otro Admin",
  "email": "admin2@gestor.com",
  "password": "admin123",
  "role_id": 1,
  "sucursal_id": null
}
```

**Body (JSON) - Crear Agente:**
```json
{
  "name": "María López",
  "email": "maria.agente@gestor.com",
  "password": "agente123",
  "role_id": 2,
  "sucursal_id": 1
}
```

**Respuesta exitosa (201):**
```json
{
  "message": "Usuario creado exitosamente",
  "user": {
    "id": 6,
    "name": "María López",
    "email": "maria.agente@gestor.com",
    "role_id": 2,
    "sucursal_id": 1,
    "role": {
      "id": 2,
      "nombre": "Agente"
    },
    "negocio": {
      "id": 1,
      "nombre": "Sucursal Centro"
    }
  }
}
```

**Respuesta de error (403) - Si no eres admin:**
```json
{
  "error": "No tienes permisos para crear usuarios"
}
```

---

## 🔒 Obtener Usuario Actual

**Endpoint:** `GET /api/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "id": 1,
  "name": "Administrador",
  "email": "admin@gestor.com",
  "role_id": 1,
  "sucursal_id": null,
  "role": {
    "id": 1,
    "nombre": "Administrador"
  },
  "negocio": null
}
```

---

## 🚪 Cerrar Sesión

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "message": "Sesión cerrada"
}
```

---

## 👤 Usuario Administrador por Defecto

**Email:** `admin@gestor.com`  
**Password:** `admin123`  
**Rol:** Administrador (role_id: 1)

> ⚠️ **Importante:** Cambia esta contraseña en producción

---

## 🔐 Flujo de Trabajo Recomendado

### 1️⃣ Desde la PWA (Registro público):
- Los usuarios se registran con email y contraseña
- Automáticamente obtienen rol de **Cliente**
- Pueden solicitar turnos

### 2️⃣ Desde Insomnia/Postman (Administradores):
1. Login con usuario admin: `POST /api/login`
2. Usar el token recibido
3. Crear usuarios Admin/Agente: `POST /api/users/create` con el token en el header

### 3️⃣ Agentes:
- Se crean desde Insomnia por el Admin
- Se les asigna una `sucursal_id`
- Login normal: `POST /api/login`
- Acceden a atender turnos de su sucursal

---

## 📋 Ejemplos en Insomnia

### Crear un Agente:
```bash
POST http://127.0.0.1:8000/api/users/create
Authorization: Bearer {token_admin}
Content-Type: application/json

{
  "name": "Carlos Agente",
  "email": "carlos@sucursal1.com",
  "password": "agente123",
  "role_id": 2,
  "sucursal_id": 1
}
```

### Crear otro Administrador:
```bash
POST http://127.0.0.1:8000/api/users/create
Authorization: Bearer {token_admin}
Content-Type: application/json

{
  "name": "Ana Administradora",
  "email": "ana@gestor.com",
  "password": "admin456",
  "role_id": 1,
  "sucursal_id": null
}
```

---

## ✅ Resumen de Seguridad

- ✅ **Registro público** solo crea Clientes (role_id = 3)
- ✅ **Crear Admin/Agente** requiere autenticación y ser Administrador
- ✅ Los tokens son generados por Laravel Sanctum
- ✅ Las contraseñas se encriptan automáticamente con bcrypt
- ✅ Cada usuario tiene su relación con `role` y `negocio` (si aplica)
