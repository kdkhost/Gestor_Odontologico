# Gestor Odontologico

Sistema multiunidade para clinica odontologica desenvolvido em Laravel 12, PHP 8.4+, AdminLTE 3.2 no painel administrativo, Filament 4 no core operacional e MariaDB como banco principal, com SQLite opcional apenas para testes ou homologacao local.

## Visao rapida

- instalador automatico web em `/instalar`;
- painel administrativo multiunidade em `/admin` com shell AdminLTE 3.2;
- portal do paciente em `/portal`;
- documentacao HTML operacional em `/documentacao/`;
- publicacao oficial do codigo em `https://github.com/kdkhost/Gestor_Odontologico`.

## Versao publicada

- versao base atual: `1.16.0`
- release local com commit humanizado e repositorio limpo

## Escopo atual

O projeto ja esta preparado para:

- multiplas unidades;
- painel administrativo em `/admin` com shell AdminLTE 3.2;
- portal do paciente em `/portal`;
- solicitacao publica de consulta em `/`;
- instalador automatico em `/instalar`;
- agenda visual com FullCalendar 4;
- documentos digitais com aceite;
- financeiro com contas a receber, parcelamento interno e Mercado Pago;
- estoque por unidade;
- templates operacionais de WhatsApp;
- regua automatica de lembrete, cobranca preventiva e reativacao;
- dashboard executivo com widgets operacionais;
- central operacional com prioridades acionaveis;
- central de BI com metas, ranking profissional, comissao e exportacao CSV;
- perfil 360 do paciente;
- governanca de webhook com idempotencia;
- protecao central contra conflito de agenda;
- central tecnica de saude do sistema;
- central de repasses com fechamento, pagamento e conciliacao;
- gestao detalhada de repasses com comprovante anexado;
- importacao de extrato para conciliacao assistida, incluindo OFX e perfil por banco;
- central de faturamento fiscal com NFSe manual/homologacao, fila e protocolo;
- central LGPD com exportacao e anonimização assistida;
- central de governanca clinica com controle de prontuario, documentos e retorno;
- central de autorizacoes de convenio com guia interna, retorno operacional e exportacao JSON estruturada;
- central de faturamento de convenio com lote, glosa e reapresentacao TISS-ready;
- manutencao com whitelist;
- PWA com push e modo app quando instalado.
- modulos nativos no AdminLTE para agenda administrativa, pacientes e perfil 360 do paciente.

## Painel administrativo

O sistema agora trabalha com duas camadas complementares:

- `AdminLTE 3.2` em `/admin` para login, dashboard, navegacao principal e modulos nativos mais usados;
- `Filament core` em `/admin/core` para recursos legados, telas avancadas e migracao gradual sem perda de operacao.

Modulos ja migrados para tela nativa:

- `Agenda administrativa`
- `Pacientes`
- `Perfil 360 do paciente`

## Stack tecnica

- Laravel 12
- PHP 8.4+
- AdminLTE 3.2
- Filament 4 no core interno
- Livewire 3
- MariaDB como padrao
- SQLite apenas para testes ou ambiente local opcional
- Spatie Permission
- Spatie Activitylog
- DomPDF
- Web Push / VAPID

## Perfis previstos

- `superadmin`
- `admin-unidade`
- `recepcao`
- `dentista`
- `financeiro`
- `estoque`
- `paciente`

As permissoes sao modulares e geradas por acao e modulo a partir de `config/clinic.php`.

## Fluxo principal

1. O paciente solicita um horario no site publico.
2. A recepcao confirma ou ajusta a agenda.
3. O atendimento evolui no prontuario e no plano de tratamento.
4. Documentos sao apresentados e aceitos digitalmente.
5. O financeiro acompanha cobranca, parcelas e status.
6. Templates de WhatsApp apoiam a comunicacao operacional.
7. A comissao e calculada a partir do financeiro pago.
8. O repasse e fechado, pago, documentado, importado no extrato e conciliado no painel.
9. O faturamento fiscal pode transformar contas pagas em NFSe com fila, protocolo e emissao rastreavel.
10. O convenio pode sair da guia autorizada para lote faturado, retorno da operadora e reapresentacao de glosa.

## Instalacao

### Via instalador web

Use a rota `/instalar`. O instalador:

- valida requisitos tecnicos;
- valida diretorios gravaveis;
- testa a conexao com o banco;
- grava o `.env`;
- gera a chave da aplicacao;
- executa migracoes e seed;
- cria o primeiro superadmin;
- registra a instalacao.

Se `/instalar` responder `404` no cPanel, confirme primeiro que os arquivos [index.php](g:\Tudo\MEU-SISTEMA\CLINICA%20ODONTOLOGICA\index.php) e [.htaccess](g:\Tudo\MEU-SISTEMA\CLINICA%20ODONTOLOGICA\.htaccess) da raiz foram enviados junto com o projeto. Eles sao os responsaveis por encaminhar as rotas amigaveis para o Laravel sem expor `/public` na URL.

Se o sistema ainda nao estiver instalado e a aplicacao estiver sem `APP_KEY`, o bootstrap agora injeta uma chave temporaria apenas para o fluxo do instalador. Isso evita `MissingAppKeyException` em `/instalar` sem mascarar erro real depois que a instalacao for concluida.

O login administrativo tambem passou a tolerar ausencia das tabelas `sessions` e `cache` no banco: o sistema recua para sessao em arquivo e ignora cache persistente de configuracoes nessa etapa, evitando erro 500 logo em `/admin/login`.

Quando o sistema ainda nao esta instalado, o bootstrap agora tambem ignora cache antigo de configuracao e rotas para nao prender o servidor em estado anterior. E, se alguem abrir `/admin/login` antes da instalacao, o painel passa a redirecionar para `/instalar`.

### Via terminal

```bash
composer install
cp .env.example .env
# edite o .env com os dados reais do MariaDB antes de continuar
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

### Cron principal recomendado

```bash
* * * * * /usr/local/bin/php /home/USUARIO/public_html/Gestor_Odontologico/artisan schedule:run >> /dev/null 2>&1
```

## Requisitos tecnicos

- PHP 8.4 ou superior
- extensoes:
  - `pdo`
  - `pdo_mysql`
  - `pdo_sqlite` apenas se voce realmente quiser usar SQLite local
  - `openssl`
  - `mbstring`
  - `json`
  - `fileinfo`
  - `curl`
  - `gd`
  - `zip`
- permissao de escrita em `storage/` e `bootstrap/cache/`

## Configuracoes importantes

As variaveis principais ficam em `.env.example`:

- dados da aplicacao;
- versao do sistema;
- dados do desenvolvedor para o rodape do admin;
- Mercado Pago;
- Evolution API;
- ViaCEP;
- Web Push;
- regras globais de seguranca do WhatsApp;
- parametros da automacao operacional;
- configuracao do tour inicial.

## Rodape do admin

O rodape do painel exibe:

- informacoes do desenvolvedor;
- versao do sistema;
- versao atual do PHP;
- botao para reiniciar o tour.

Esses dados podem ser ajustados pelo painel em `Configuracoes do sistema`.

## Tour inicial

O painel possui um tour guiado reiniciavel para orientar:

- navegacao lateral;
- agenda visual;
- templates de WhatsApp;
- BI e metas;
- central de repasses;
- gestao detalhada de repasses;
- importacao de extrato com OFX;
- faturamento fiscal e notas fiscais;
- autorizacoes de convenio;
- faturamento de convenio;
- privacidade e LGPD;
- governanca clinica;
- configuracoes do sistema.

## WhatsApp operacional

Os templates podem ser personalizados no painel. A base tambem preve guardrails para reduzir risco operacional:

- intervalo minimo entre envios;
- limite por destinatario;
- limite por minuto;
- janela de envio comercial;
- exigencia de opt-in quando configurada;
- logs de bloqueio e envio;
- formatacao final adequada para WhatsApp.

Observacao importante: nenhuma automacao consegue garantir sozinha que um numero nunca sera restringido. O sistema aplica protecao operacional, mas o uso ainda depende de opt-in, conteudo adequado e politica oficial do canal.

## Automacao operacional

O painel possui a pagina `Configuracoes > Automacao`, com:

- lembrete automatico para consultas confirmadas;
- aviso preventivo de parcelas abertas ou vencendo;
- reativacao controlada de pacientes sem retorno;
- modo de previa sem disparo real;
- log de execucao com contagem de encontrados, enviados, ignorados e falhos;
- exigencia de opt-in explicito no cadastro publico e no portal do paciente.

No cPanel, configure o cron:

```bash
php /caminho/do/projeto/artisan schedule:run >> /dev/null 2>&1
```

## BI, metas e comissao

O painel possui uma central de `BI e metas` com:

- leitura por periodo e por unidade;
- receita recebida, producao concluida, ticket medio e novos pacientes;
- ranking de profissionais com receita e comissao;
- metas individuais por profissional com progresso real;
- exportacao CSV de resumo, profissionais e metas;
- calculo automatico de comissao a partir da conta a receber paga;
- leitura financeira dos repasses pagos, conciliados e aguardando conciliacao.

Para reprocessar comissoes de contas antigas ja pagas:

```bash
php artisan clinic:commission-sync
```

## Repasse de comissao

O sistema possui uma `Central de repasses` no painel administrativo com:

- leitura de comissoes pendentes prontas para fechamento;
- fechamento por profissional e por periodo;
- historico recente de lotes fechados;
- baixa rapida do pagamento do repasse;
- comando de fechamento em lote para uso operacional.

Tambem existe a tela `Repasses detalhados`, com:

- forma de pagamento do repasse;
- referencia bancaria;
- comprovante anexado;
- usuario responsavel pelo pagamento;
- referencia do extrato bancario;
- usuario responsavel pela conciliacao;
- observacoes de pagamento e conciliacao.

Comando de fechamento:

```bash
php artisan clinic:commission-close --from=2026-03-01 --to=2026-03-31
```

## Importacao de extrato

O sistema possui a central `Extrato e conciliacao`, com:

- upload de arquivo CSV, TXT ou OFX do banco;
- selecao de perfil bancario para melhorar o mapeamento das colunas;
- deteccao automatica de delimitador;
- leitura de colunas de data, descricao, valor e referencia;
- parser OFX para movimentos financeiros em arquivos bancarios padrao;
- sugestao de conciliacao por valor, referencia, data e descricao;
- aplicacao individual ou em lote das sugestoes confiaveis;
- historico de importacoes com total de linhas, sugestoes e conciliacoes efetivas.

## Faturamento fiscal e NFSe

O painel possui a central `Faturamento fiscal`, com:

- leitura de contas pagas elegiveis para emissao fiscal;
- bloqueio automatico quando faltam dados fiscais da unidade;
- criacao de rascunhos de NFSe individuais ou em lote;
- fila fiscal para protocolo de envio;
- registro de RPS, protocolo, numero municipal e codigo de verificacao;
- resource administrativo para acompanhar rascunho, fila, protocolo, emissao e cancelamento;
- suporte atual aos perfis `manual` e `mock / homologacao`, preparando a base para integracao municipal futura.

Para habilitar o fluxo, configure na unidade:

- razao social;
- CNPJ;
- inscricao municipal;
- codigo do municipio do servico;
- codigo de servico padrao;
- aliquota ISS padrao;
- serie RPS;
- CNAE quando aplicavel.

Para processar a fila pelo terminal:

```bash
php artisan clinic:nfse-submit --limit=30
```

## Privacidade e LGPD

O painel possui a `Central LGPD`, com:

- abertura de solicitacoes de exportacao ou anonimização do cadastro;
- prazo operacional por solicitacao;
- processamento assistido com trilha de quem solicitou e quem executou;
- exportacao estruturada em JSON salvo em area protegida do sistema;
- download administrativo protegido do pacote gerado;
- anonimização segura do cadastro, responsaveis, usuario do portal e assinaturas PWA;
- preservacao declarada de modulos clinicos, financeiros, documentais e fiscais sob base legal de retencao.

## Governanca clinica

O painel possui a `Central de governanca clinica`, com:

- consultas concluidas sem prontuario clinico;
- pacientes em tratamento com documentacao obrigatoria pendente;
- itens planejados vencidos sem conclusao;
- planos aprovados sem retorno futuro agendado;
- leitura gerencial para recepcao, coordenacao clinica e administrativo.

## Convenios e autorizacoes

O painel possui a `Central de autorizacoes de convenio`, com:

- leitura de planos aprovados elegiveis para gerar guia;
- criterio automatico por convenio que exige autorizacao ou procedimento que pede aprovacao;
- guia em rascunho, envio para operadora, retorno autorizado, parcial ou negado;
- expiracao automatica de guias vencidas;
- exportacao JSON estruturada, pronta para futura integracao TISS ou faturamento assistido;
- configuracao de convenio com ANS, documento da operadora, canal padrao, validade e tabela TISS.

Comando operacional da camada:

```bash
php artisan clinic:insurance-authorizations-expire
```

## Faturamento de convenio e TISS-ready

O painel possui a `Central de faturamento de convenio`, com:

- agrupamento automatico de itens autorizados e executados por convenio e competencia;
- criacao de lotes em rascunho com guias e itens faturados;
- envio do lote para operadora com numero de lote e guias internas;
- registro de retorno integral ou parcial, com glosa por item;
- reapresentacao de glosa em lote novo, preservando o vinculo com o item original;
- exportacao JSON estruturada em formato interno `TISS-ready`, pronta para futura integracao real.

Comando operacional da camada:

```bash
php artisan clinic:insurance-claims-create-drafts --competence=2026-03
```

## Webhooks

- Mercado Pago: `/webhooks/mercadopago`
- Evolution API: `/webhooks/evolution`

Os eventos recebidos sao registrados com trilha, hash de payload e protecao contra duplicidade.

## PWA

O sistema expoe:

- manifest em `/pwa/manifest.json`;
- service worker em `/pwa/sw.js`;
- icones em `public/icons/`.

Quando instalado, o portal assume comportamento visual de app. No navegador comum, mantem layout responsivo tradicional.

## Testes

Validacao usada no projeto:

```bash
php -c .tools/php.ini vendor/bin/phpunit
```

## Node.js em producao

Nao e obrigatorio para publicar o sistema em producao com o pacote entregue. O projeto so precisa de Node.js se voce quiser recompilar assets ou trabalhar com Vite no ambiente local.

## Entregavel empacotado

O pacote gerado do codigo-fonte fica em:

- `g:\Tudo\MEU-SISTEMA\odonto-flow-codigo-fonte.zip`

## Documentacao HTML

Existe uma documentacao HTML funcional de implantacao, cron e configuracao em:

- `public/documentacao/index.html`

URL sugerida apos publicar o sistema:

- `/documentacao/`

## Proximas camadas recomendadas

- integracao municipal real de NFSe por provedor e cidade;
- integracao TISS real com operadoras, protocolo externo e retorno por arquivo ou API;
- importacao OFX/CSV com mapeamento avancado por layout especifico de banco;
- assinatura digital mais forte em documentos;
- BI com metas comparativas por equipe e por especialidade.
