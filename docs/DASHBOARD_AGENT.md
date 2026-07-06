# Dashboard Personal del Operador

La idea es que el operador vea únicamente información accionable para mejorar su desempeño durante el día.

```
┌──────────────────────────────────────────────────────────────────────┐
│ Buenos días, Fernando                                                │
│ Lunes 06 Julio 2026                                                  │
│ Turno: 07:00 - 15:00     Equipo: CSS-01     Supervisor: María Pérez  │
└──────────────────────────────────────────────────────────────────────┘
```

---

# 1. Score del Día

Widget principal (Hero)

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

       SCORE OPERATIVO

           91 / 100

🟢 Excelente

↑ +3 respecto ayer

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

Este score se calcula con:

* Adherencia
* Puntualidad
* Tiempo disponible
* Calidad
* Productividad
* Cumplimiento de pausas

No mostrar la fórmula completa; solo un enlace a "¿Cómo se calcula?".

---

# 2. Estado Actual

```
🟢 En llamada

Llamada actual

06:14

Tiempo logueado

04:28

Tiempo restante turno

03:32
```

Si está en break:

```
☕ Almuerzo

18 de 45 min

Quedan

27 min
```

---

# 3. Mi Jornada

Timeline

```
07:00 ✔ Login

09:30 ✔ Break

11:30 ✔ Almuerzo

14:00 ○ Break

15:00 ○ Salida
```

Permite visualizar rápidamente qué falta del turno.

---

# 4. Mi Productividad Hoy

Cards

```
Llamadas

42

Meta

50

84%
```

```
Tiempo hablado

2h 41m
```

```
Tiempo ACW

21 min
```

```
Tiempo disponible

57 min
```

---

# 5. Mi Comparación

Aquí aparece el componente comparativo.

No comparar contra toda la operación.

Comparar únicamente contra:

* promedio del equipo
* mejor operador del equipo
* promedio personal últimos 30 días

```
                 Yo

██████████

Equipo

████████

Top

████████████
```

Ejemplo

| Indicador  |   Yo | Equipo | Mejor |
| ---------- | ---: | -----: | ----: |
| Llamadas   |   42 |     38 |    57 |
| AHT        | 5:20 |   5:48 |  4:55 |
| Calidad    |  96% |    93% |   98% |
| Adherencia |  98% |    94% |   99% |

El operador entiende inmediatamente dónde está.

---

# 6. Adherencia

Widget Gauge

```
█████████░

98%

Excelente
```

Detalle

```
Programado

8h

Logueado

7h 56m

Desvío

-4 min
```

---

# 7. Mi Disponibilidad

Donut

```
En llamada

48%

Disponible

19%

ACW

11%

Break

9%

Lunch

13%
```

Es uno de los widgets favoritos en WFM.

---

# 8. Calidad

```
Calidad

96%

★★★★★
```

Subindicadores

```
Protocolos

98%

Empatía

94%

Documentación

95%
```

---

# 9. Objetivos del Día

Checklist

```
☑ Login puntual

☑ Break 1

☑ Almuerzo

☐ 50 llamadas

☑ Calidad >95%

☑ Adherencia >90%
```

Da sensación de progreso.

---

# 10. Tendencia Personal

Mini gráfico

```
Score últimos 7 días

95

93

94

90

91

92

91
```

El operador compite contra sí mismo.

---

# 11. Alertas

```
🟢 Excelente

Tu AHT está 18 segundos mejor
que el promedio del equipo.
```

o

```
🟡 Atención

Has utilizado el 90%
de tu tiempo de ACW.
```

o

```
🔴 Riesgo

Superaste tu tiempo
de almuerzo.
```

Las alertas deben ser en tiempo real.

---

# 12. Próxima Actividad

Aprovechando el módulo de planificación intradía existente, mostrar siempre el siguiente evento asignado.

```
Próxima actividad

14:00

Coaching

Sala 3

Duración

30 min
```

---

# 13. Mi Ranking

No mostrar un ranking completo (genera competencia negativa).

Mostrar únicamente la posición del operador.

```
Mi posición

12 / 86

↑ Subiste 3 posiciones

Percentil

86%
```

También puede mostrarse:

```
Estás dentro del

Top 15%

del equipo
```

Esto motiva sin exponer el rendimiento individual de otros agentes.

---

# 14. Reconocimientos

```
🏅 15 días sin tardanzas

⭐ Calidad superior al 95%

🔥 5 días consecutivos
cumpliendo objetivos

🎯 Top 10% del equipo
```

Este componente aporta un elemento de gamificación y reconocimiento sin afectar la privacidad.

---

## Distribución recomendada

```
┌───────────────┬──────────────────────────────┐
│ Score         │ Estado actual                │
├───────────────┼──────────────────────────────┤
│ Mi jornada    │ Próxima actividad            │
├───────────────┼──────────────────────────────┤
│ Productividad │ Adherencia                   │
├───────────────┼──────────────────────────────┤
│ Comparativa con el equipo                    │
├──────────────────────────────────────────────┤
│ Disponibilidad │ Calidad │ Objetivos         │
├──────────────────────────────────────────────┤
│ Tendencia │ Ranking │ Reconocimientos        │
└──────────────────────────────────────────────┘
```

### Ventajas para el WFM

* **Operador:** conoce su estado actual y qué debe hacer para cumplir sus objetivos.
* **Supervisor:** reduce consultas sobre horarios, pausas y desempeño, ya que el operador dispone de la información en tiempo real.
* **WFM:** incentiva la adherencia y el cumplimiento del horario sin necesidad de supervisión constante.
* **Operación:** la comparación se realiza contra el promedio del equipo y el histórico personal, evitando fomentar una competencia excesiva entre agentes.
