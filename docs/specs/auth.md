# Auth API Spec

## Endpoints

### POST /auth/register
Registra un nuevo usuario con cuenta y saldo inicial.

**Request:**
```json
{
  "name": "Juan Pérez",
  "password": "secret123",
  "sala_code": "SALA001"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "unique_id": "TFJOTJQEL4P3",
      "name": "Juan Pérez"
    }
  }
}
```

**Errores:**
- `400`: Código de sala inválido o usado
- `422`: Validación de datos fallida

---

### POST /auth/login
Inicia sesión y retorna token JWT.

**Request:**
```json
{
  "unique_id": "TFJOTJQEL4P3",
  "password": "secret123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "unique_id": "TFJOTJQEL4P3",
      "name": "Juan Pérez"
    }
  }
}
```

**Errores:**
- `401`: Credenciales inválidas

---

### GET /auth/sala/{code}
Valida si un código de sala es válido.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "valid": true
  }
}
```

## Reglas de Negocio
- El código de sala solo puede usarse una vez
- La contraseña debe tener al menos 6 caracteres
- El nombre debe tener al menos 2 caracteres
- Al registrarse, se crea automáticamente una cuenta con saldo inicial aleatorio (100-500)
- El ID único del usuario se genera en formato `TFJOTJQEL4P3` (12 caracteres alfanumérico)
