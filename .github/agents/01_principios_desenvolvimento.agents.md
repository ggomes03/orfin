# Backend Rules (DDD + TDD + SOLID)

## Objective
- Isolar as regras de negocio na camada Domain.
- Manter baixo acoplamento e alta testabilidade.

## Layers
- Domain: contem regras de negocio, Entities, ValueObjects, interfaces de repositorio e servicos de dominio.
- Application: contem UseCases e DTOs; orquestra o Domain.
- Infrastructure: contem Eloquent, repositorios concretos e integracoes externas.
- Interface: contem Controllers, Requests e Routes.

## Hard Rules
- Domain nao depende de framework, banco de dados ou HTTP.
- Application depende de Domain (nunca de Infrastructure).
- Infrastructure implementa contratos definidos no Domain.
- Interface apenas recebe entrada e delega para UseCase.
- Toda logica de negocio fica somente no Domain.
- Eloquent fica apenas na Infrastructure.

## Dependency Direction
- Permitido: Interface -> Application -> Domain.
- Permitido: Infrastructure -> Domain.
- Proibido: Domain -> qualquer outra camada.
- Proibido: Application -> Infrastructure.

## TDD Flow
1. Escrever um teste que falha.
2. Implementar o minimo necessario para passar.
3. Refatorar mantendo os testes verdes.

## SOLID Checklist
- S: cada classe deve ter uma unica responsabilidade.
- O: permitir extensao sem alterar comportamento estavel.
- L: implementacoes devem respeitar os contratos.
- I: preferir interfaces pequenas e especificas.
- D: depender de abstracoes, nao de concretos.

## Design Layer (DTO + Strategy + OO)
- DTO e obrigatorio para entrada e saida de UseCases (nao trafegar arrays soltos entre camadas).
- DTO representa contratos explicitos da Application e reduz acoplamento com HTTP/Eloquent.
- Strategy e obrigatoria quando houver variacao de algoritmo/regra por contexto (evitar crescimento de if/else).
- A selecao de Strategy deve ocorrer por interface/contrato; implementacoes de infra ficam fora do Domain.
- Priorizar encapsulamento, composicao e polimorfismo em vez de logica procedural.
- Cada classe deve ter responsabilidade clara e proteger seu estado interno.
- Preferir ValueObjects para regras e validacoes reutilizaveis de negocio.

## Application Patterns (Factory + Repository + Service + Validation + Errors)
- Factory deve ser usada para criacao de objetos complexos/agregados, evitando construcao espalhada em Controller/UseCase.
- Repository e obrigatorio para acesso a dados; Domain/Application nao acessam Eloquent diretamente.
- Repository deve expor contratos de negocio, sem vazar detalhes de query builder para camadas acima.
- Service layer e usada para orquestrar processos de aplicacao com multiplos UseCases/Repositories.
- Service nao substitui Domain: a regra de negocio central permanece no Domain.
- Validacao de entrada HTTP deve ser feita com FormRequest (evitar validacao inline em Controller).
- FormRequest deve converter/normalizar dados de entrada antes de entregar ao UseCase/DTO, quando necessario.
- Erros de negocio devem usar exceptions personalizadas por contexto (ex.: `InsufficientBalanceException`).
- Exceptions personalizadas devem ser tratadas de forma consistente na Interface, com respostas HTTP previsiveis.
- Nao usar exceptions genericas para regra de negocio quando houver significado de dominio explicito.

## Language and Documentation Standards
- Escrever codigo, nomes de classes/metodos/variaveis e comentarios em portugues, mantendo consistencia no projeto.
- Evitar mistura de idiomas no dominio (nao alternar portugues e ingles sem necessidade tecnica).
- Todas as funcoes/metodos devem conter PHPDoc.
- PHPDoc deve descrever objetivo, parametros (`@param`), retorno (`@return`) e excecoes relevantes (`@throws`).
- PHPDoc deve ser atualizado sempre que a assinatura ou o comportamento da funcao mudar.

## DoD
- UseCase com testes cobrindo as regras de negocio.
- Sem dependencia de infraestrutura em Domain/Application.
- Separacao de camadas clara e verificavel.