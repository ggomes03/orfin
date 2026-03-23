# Testing Rules (Mocking Required)

## Mandatory
- Sempre usar mocks/fakes para dependencias externas.
- Nunca usar infraestrutura real em testes unitarios.

## Never Use in Unit Tests
- Banco real.
- API externa real.
- File system real.
- Email/Queue/Storage reais.

## Determinism
- Congelar tempo quando relevante.
- Controlar random/UUID.
- Simular erros e casos limite (timeout, 500, vazio).
- Nao depender de estado externo.

## Focus
- Testar Domain e UseCases.
- Validar regras de negocio, nao comportamento interno de framework.

## Test Quality Gate
- Rapido: sem rede e sem disco.
- Isolado: sem estado compartilhado.
- Deterministico: mesmo resultado sempre.
- Cobertura minima: regra de negocio principal e edge cases.