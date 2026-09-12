# Auth API Spec

## Endpoints

### POST /auth/join
Registra un nuevo usuario con cuenta y saldo inicial.

**Request:**
```json
{
  "roomCode": "SALA001",
  "name": "Juan Pérez",
  "password": "secret123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "A8LSIWVLGZ1Q",
      "name": "Juan Pérez",
      "createdAt": "2026-09-07T10:00:00Z"
    },
    "account": {
      "id": "ACC_XK9mP2vL",
      "userId": "A8LSIWVLGZ1Q",
      "createdAt": "2026-09-07T10:00:00Z"
    },
    "sessionToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "initialBalance": 542.75
  }
}
```

**Errores:**
- `400`: Código de sala inválido
- `422`: Validación de datos fallida

---

### POST /auth/login
Inicia sesión y retorna token JWT.

**Request:**
```json
{
  "userId": "TFJOTJQEL4P3",
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
      "id": "TFJOTJQEL4P3",
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
- La contraseña debe tener al menos 6 caracteres
- El nombre debe tener al menos 2 caracteres
- Al registrarse, se crea automáticamente una cuenta con saldo inicial aleatorio (100-500)
- El ID único del usuario se genera en formato `TFJOTJQEL4P3` (12 caracteres alfanumérico)

## Modelo de Datos

### User
```typescript
{
  id: string           // VARCHAR(12), único (ej: "A8LSIWVLGZ1Q")
  name: string         // VARCHAR(50)
  passwordHash: string // VARCHAR(255)
  createdAt: Date
  updatedAt: Date
}
```

### Session
```typescript
{
  id: string              // VARCHAR(36), UUID
  userId: string          // VARCHAR(12), FK a User
  token: string           // VARCHAR(512)
  ipAddress: string       // VARCHAR(45)
  userAgent: string       // VARCHAR(512)
  status: 'ACTIVE' | 'EXPIRED'
  createdAt: Date
  expiresAt: Date
}
```
