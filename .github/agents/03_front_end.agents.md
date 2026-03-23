# Front-End Rules

## Stack Obrigatoria
- Usar `Tailwind CSS` para estilizacao.
- Usar `shadcn/ui` como base de componentes.

## Diretrizes
- Evitar CSS solto quando houver utilitario Tailwind equivalente.
- Preferir composicao de componentes `shadcn/ui` antes de criar componente do zero.
- Manter consistencia visual com tokens/classes reutilizaveis.

## Padrao de Entrega
- Layout e responsividade com Tailwind.
- Componentes de interface (Button, Input, Dialog, Table etc.) via shadcn/ui.
## 🎨 Cores e Tema

### Diretrizes
- Utilizar uma paleta de cores consistente baseada em **tokens de tema**.
- Evitar uso de cores hardcoded (ex: `text-blue-500`) fora do padrão definido.
- Priorizar classes semânticas (ex: `bg-primary`, `text-muted-foreground`).
- Manter contraste adequado para garantir legibilidade e acessibilidade.

### Centralização de Cores
- Todas as cores do projeto **devem ser centralizadas em um arquivo global de CSS** (ex: `globals.css`).
- Definir as cores utilizando **CSS Variables (`:root`)** para facilitar manutenção e escalabilidade.
- Evitar definir cores diretamente em componentes.
- Alterações de tema devem ser feitas **exclusivamente nesse arquivo central**, garantindo consistência em toda a aplicação.

Exemplo:
```css
:root {
  --primary: 37 99 235;
  --success: 22 163 74;
  --error: 220 38 38;
  --warning: 245 158 11;

  --background: 249 250 251;
  --foreground: 17 24 39;
}
```
### Aplicação
- Botões primários devem usar `primary`.
- Informações financeiras:
  - Receita → `success`
  - Despesa → `error`
- Evitar uso decorativo de cores sem significado funcional.
- Priorizar interfaces limpas com predominância de cores neutras.

### Tema (Light/Dark)
- Garantir suporte a modo claro e escuro desde o início.
- Utilizar variáveis de tema (CSS variables) para facilitar manutenção.
- Evitar definir cores diretamente nos componentes.

### Padronização
- Centralizar definição de cores no tema global (ex: `:root` ou `tailwind.config`).
- Seguir o padrão de nomenclatura do `shadcn/ui`:
  - `primary`
  - `secondary`
  - `muted`
  - `accent`
  - `destructive`