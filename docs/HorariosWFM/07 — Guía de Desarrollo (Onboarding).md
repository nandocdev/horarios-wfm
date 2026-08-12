---
tipo: guia-desarrollo
proyecto: "{{Nombre del Proyecto}}"
fecha: {{date}}
tags: [desarrollo, setup, onboarding]
---

# 🛠 Guía de Desarrollo

## 1. Requisitos Previos
- Lenguaje/Runtime: (Ej: Node v18+, Python 3.11)
- Base de Datos: (Ej: PostgreSQL 15)
- Herramientas: (Ej: Docker, PNPM, AWS CLI)

## 2. Setup Inicial
```bash
# Pasos para clonar y configurar el entorno
git clone ...
cp .env.example .env
npm install
```

## 3. Variables de Entorno
| Variable | Descripción | Valor por defecto |
| :--- | :--- | :--- |
| `DATABASE_URL` | Conexión a la DB | - |

## 4. Scripts Disponibles
- `npm run dev`: Levanta el entorno local.
- `npm run test`: Ejecuta la suite de pruebas.
- `npm run build`: Genera el bundle de producción.

## 5. Estándares del Proyecto
- **Linter/Formatter**: Prettier / ESLint.
- **Git**: Convención de commits (Conventional Commits).
- **Branching**: Gitflow / Trunk-based.

## 6. Comandos Útiles / Troubleshooting
- (Ej: Cómo resetear la base de datos local)
- (Ej: Cómo limpiar la caché del bundler)

---
**Relacionado**: [[03-Arquitectura]]
