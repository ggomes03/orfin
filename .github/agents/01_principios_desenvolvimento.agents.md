# Backend Rules (DDD + TDD + SOLID)

## Objective
- Isolar regras de negocio no Domain.
- Manter acoplamento baixo e alta testabilidade.

## Layers
- Domain: regras, Entities, ValueObjects, interfaces de repositorio, servicos de dominio.
- Application: UseCases e DTOs; orquestra Domain.
- Infrastructure: Eloquent, repositorios concretos, APIs externas.
- Interface: Controllers, Requests, Routes.

## Hard Rules
- Domain nao depende de framework, DB ou HTTP.
- Application depende de Domain (nunca de Infrastructure).
- Infrastructure implementa contratos do Domain.
- Interface apenas recebe entrada e chama UseCase.
- Logica de negocio fica somente no Domain.
- Eloquent apenas na Infrastructure.

## Dependency Direction
- Permitido: Interface -> Application -> Domain.
- Permitido: Infrastructure -> Domain.
- Proibido: Domain -> qualquer outra camada.
- Proibido: Application -> Infrastructure.

## TDD Flow
1. Escrever teste que falha.
2. Implementar minimo para passar.
3. Refatorar mantendo testes verdes.

## SOLID Checklist
- S: uma responsabilidade por classe.
- O: extensao sem alterar comportamento estavel.
- L: contratos respeitados nas implementacoes.
- I: interfaces pequenas e especificas.
- D: depender de abstracoes.

## DoD
- UseCase com testes cobrindo regra de negocio.
- Sem dependencia de infra em Domain/Application.
- Separacao de camadas clara.