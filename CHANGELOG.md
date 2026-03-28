# Changelog

## [1.15.1] - 2026-03-28

### Ajustado
- MariaDB passou a ser o banco padrao no `.env.example`, no fallback do `config/database.php` e nas configuracoes de fila em banco;
- instalador agora trata `pdo_sqlite` como extensao opcional, sem bloquear ambientes que usam apenas MariaDB;
- formulario do instalador passou a abrir com MariaDB preselecionado, host `localhost` e campo de database em branco;
- documentacao e README agora deixam explicito que o `.env` precisa ser criado antes de rodar `key:generate` e `migrate` em producao.

## [1.15.0] - 2026-03-27

### Adicionado
- central de faturamento de convenio com lotes por competencia, retorno da operadora e reapresentacao de glosa;
- estruturacao de lote, guia e item faturado com exportacao JSON TISS-ready interna;
- comando `clinic:insurance-claims-create-drafts` para gerar rascunhos de faturamento por competencia;
- camada de retorno financeiro do convenio com totais faturado, recebido e glosado no proprio lote;
- cobertura automatizada para criar lote, registrar retorno parcial e abrir reapresentacao.

### Ajustado
- tour inicial agora apresenta a nova central de faturamento de convenio;
- documentacao operacional foi expandida com cron e fluxo de faturamento TISS-ready;
- versao base do sistema atualizada para 1.15.0.

## [1.14.0] - 2026-03-27

### Adicionado
- central de autorizacoes de convenio com fila de rascunho, envio, retorno, negacao e expiracao;
- estruturacao de guias e itens autorizados vinculados ao plano de tratamento e aos procedimentos;
- exportacao administrativa em JSON estruturado, pronta para futura integracao TISS;
- configuracoes de convenio com ANS, canal padrao, validade da autorizacao e tabela TISS;
- comando `clinic:insurance-authorizations-expire` para vencimento automatico das guias fora da validade;
- cobertura automatizada para criacao, retorno, exportacao e expiracao de autorizacoes.

### Ajustado
- tour inicial passou a apresentar a nova central de convenios;
- documentacao HTML e README agora detalham a camada de autorizacoes e o cron correspondente;
- versao base do sistema atualizada para 1.14.0.

## [1.13.0] - 2026-03-27

### Adicionado
- central de governanca clinica no painel admin com foco em qualidade assistencial;
- leitura de consultas concluidas sem prontuario, planos sem retorno futuro, documentos pendentes e itens vencidos;
- cobertura automatizada para snapshot de governanca clinica por unidade.

### Ajustado
- tour inicial passou a apresentar a nova camada de governanca clinica;
- versao base do sistema atualizada para 1.13.0.

## [1.12.0] - 2026-03-27

### Adicionado
- central LGPD no painel admin com fila de solicitacoes, prazo operacional e processamento assistido;
- exportacao estruturada dos dados do paciente em JSON com download protegido;
- anonimizacao segura do cadastro do paciente, responsaveis, usuario do portal e assinaturas PWA;
- trilha de solicitacoes LGPD com status, base legal, observacoes e resultado processado;
- cobertura automatizada para exportacao e anonimizacao.

### Ajustado
- tour inicial passou a apresentar a nova central de privacidade;
- base do paciente agora registra data da ultima exportacao e anonimização.

## [1.11.1] - 2026-03-27

### Ajustado
- alinhamento da identidade publica do repositorio para `Gestor Odontologico`;
- documentacao principal do GitHub expandida com links, versao publicada e cron principal;
- configuracao padrao de nome do sistema e versao atualizadas no arquivo de ambiente de exemplo.

## [1.11.0] - 2026-03-27

### Adicionado
- central `Faturamento fiscal` com leitura de contas pagas elegiveis para NFSe;
- resource administrativo `Notas fiscais` com fila, protocolo, emissao e cancelamento;
- persistencia de notas fiscais, snapshots do tomador, payload do provedor e retorno operacional;
- campos fiscais na unidade para sustentar o fluxo de NFSe por unidade;
- comando `clinic:nfse-submit` para processar a fila fiscal via scheduler ou terminal;
- cobertura automatizada do ciclo de rascunho, fila, protocolo, emissao e cancelamento.

### Ajustado
- tour inicial agora apresenta a camada fiscal do sistema;
- documentacao passou a orientar a configuracao fiscal minima da unidade.

## [1.10.0] - 2026-03-27

### Adicionado
- suporte a importacao OFX na central de extrato;
- selecao de perfil por banco para melhorar o mapeamento de colunas e referencias;
- parser OFX com leitura de data, valor, descricao e identificador do movimento;
- cobertura automatizada para importacao OFX com sugestao de conciliacao.

### Ajustado
- central de extrato agora aceita CSV, TXT e OFX no mesmo fluxo administrativo;
- documentacao e tour inicial passaram a mencionar o uso de OFX e perfil por banco.

## [1.9.0] - 2026-03-27

### Adicionado
- central `Extrato e conciliacao` no painel admin para importar arquivos CSV ou TXT do banco;
- persistencia de importacoes e linhas de extrato com historico, sugestoes e conciliacoes aplicadas;
- sugestao assistida de conciliacao por valor, referencia, data e descricao do lancamento;
- aplicacao individual ou em lote das sugestoes de conciliacao abertas;
- cobertura automatizada para importacao de extrato e conciliacao assistida do repasse.

### Ajustado
- tour inicial do painel passou a apresentar a nova central de extrato;
- documentacao do sistema foi atualizada com a etapa de importacao e conciliacao assistida;
- fluxo financeiro agora cobre fechamento, pagamento, comprovante, importacao do extrato e conciliacao.

## [1.8.0] - 2026-03-27

### Adicionado
- gestao detalhada de repasses com registro de forma de pagamento, referencia bancaria e comprovante anexado;
- conciliacao bancaria manual do repasse com referencia de extrato, usuario responsavel e observacoes;
- badge de pendencia na navegacao para repasses fechados ou pagos sem conciliacao;
- indicadores de repasse pago, repasse conciliado e repasse aguardando conciliacao na central de BI;
- cobertura automatizada para ciclo completo de fechamento, pagamento com comprovante e conciliacao do repasse.

### Ajustado
- central de repasses agora descreve corretamente os estados aguardando pagamento, aguardando conciliacao e conciliado;
- tour inicial do painel passou a apresentar a gestao detalhada de repasses;
- exportacao CSV do BI passou a incluir colunas de repasse pago e conciliacao.

## [1.7.0] - 2026-03-27

### Adicionado
- progresso real de metas individuais por profissional dentro da central de BI;
- exportacao de metas com identificacao do tipo de escopo;
- cobertura automatizada para metas individuais e exportacao de metas.

### Ajustado
- leitura de metas agora respeita o escopo especifico de cada registro, inclusive profissional e unidade;
- cadastro administrativo de metas ficou mais claro sobre o tipo de escopo configurado.

## [1.6.0] - 2026-03-27

### Adicionado
- central de repasses com fechamento de comissao por profissional e periodo;
- lote formal de repasse com historico, status e baixa do pagamento;
- comando `clinic:commission-close` para fechamento em lote via console;
- testes automatizados cobrindo o ciclo de fechamento e pagamento do repasse.

### Ajustado
- BI agora separa comissao pendente de comissao ja paga;
- calculo automatico de comissao preserva estado de lotes ja fechados.

## [1.5.0] - 2026-03-27

### Adicionado
- central de BI e metas no painel admin com filtros por periodo e unidade, ranking de profissionais e exportacao CSV;
- cadastro administrativo de metas financeiras e operacionais;
- calculo automatico de comissao vinculado a conta a receber paga;
- comando `clinic:commission-sync` para reprocessar comissoes de contas ja quitadas;
- testes automatizados para comissao, snapshot de BI e exportacao gerencial.

### Ajustado
- onboarding expandido para apresentar a nova central de BI;
- trilha financeira agora alimenta comissao de forma centralizada no dominio do sistema.

## [1.4.0] - 2026-03-27

### Adicionado
- central de automacao no painel admin para configurar regua operacional de lembrete de consulta, cobranca preventiva e reativacao de pacientes;
- scheduler do Laravel preparado para cron do cPanel executando a regua automatica a cada 15 minutos;
- opt-in explicito e rastreavel de WhatsApp no agendamento publico, cadastro do portal e cadastro interno do paciente;
- logs de execucao da automacao com totais de encontrados, enviados, ignorados e falhos;
- testes automatizados cobrindo previa da automacao e persistencia do opt-in no portal.

### Ajustado
- correcao da logica de cooldown da automacao para impedir reenvio antes do prazo configurado;
- seed inicial com templates de lembrete de consulta e reativacao ja disponiveis no painel;
- tour do painel ampliado para incluir a nova central de automacao.

## [1.3.0] - 2026-03-27

### Adicionado
- protecao de conflito de agenda no nivel do dominio para impedir sobreposicao de horario por profissional, cadeira/sala e paciente;
- central tecnica no painel admin com visao de ambiente, requisitos, filas, integracoes e ultimos webhooks;
- testes automatizados cobrindo conflito de agenda e snapshot de saude do sistema.

### Ajustado
- qualquer origem de gravacao de agendamento agora respeita a mesma validacao de conflito, inclusive solicitacoes publicas;
- documentacao atualizada com os novos modulos de robustez operacional.

## [1.2.0] - 2026-03-27

### Adicionado
- perfil 360 do paciente no painel admin, com leitura unificada de cadastro, risco operacional, agenda, financeiro, tratamento e documentos;
- pagina dedicada de visualizacao de paciente em `admin/patients/{record}/perfil-360`;
- servico de insights do paciente para suportar recepcao, clinico e financeiro na mesma tela;
- idempotencia e rastreabilidade avancada de webhooks com hash de payload, contagem de tentativas e timestamps de primeira/ultima recepcao;
- sincronizacao de status de entrega/leitura/falha do WhatsApp a partir do webhook da Evolution API;
- testes automatizados para perfil 360 do paciente e duplicidade de webhook.

### Ajustado
- consolidacao de status financeiro da conta a receber a partir das parcelas processadas pelo webhook;
- tratamento de duplicidade de eventos do Mercado Pago sem criacao de transacoes repetidas.

## [1.1.0] - 2026-03-27

### Adicionado
- central operacional no painel admin com leitura consolidada de agenda, financeiro, estoque e relacionamento;
- widgets executivos no dashboard com consultas do dia, taxa de confirmacao, inadimplencia, estoque critico e taxa de no-show;
- grafico de tendencia comparando atendimentos efetivos e receita recebida em 7, 15 e 30 dias;
- servico central de inteligencia operacional com escopo multiunidade por perfil;
- testes automatizados cobrindo snapshot operacional e scoping por unidade.

### Ajustado
- remocao do widget informativo padrao do Filament para deixar o dashboard orientado ao negocio;
- documentacao do projeto em portugues com foco no sistema odontologico;
- correcao explicita do nome de tabela para modelos com pluralizacao irregular.

## [1.0.0] - 2026-03-27

### Base entregue
- instalador automatico com verificacao de requisitos e conexao;
- painel Filament com agenda visual, permissoes, portal do paciente, financeiro, estoque, templates e PWA;
- configuracoes internas de WhatsApp com guardrails operacionais;
- rodape do admin com dados do desenvolvedor, versao do sistema e PHP atual;
- tour inicial reiniciavel pelo painel.
