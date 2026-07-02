---
description: Corre deptrac y resume violaciones de bounded context en el diff actual
agent: ddd-reviewer
---

Corré `./vendor/bin/deptrac analyse --formatter=table` y resumí las violaciones
agrupadas por bounded context origen → destino. Si no hay `deptrac.yaml`, decilo
explícitamente y no inventes resultados.