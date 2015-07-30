# SBPqO-2015
Conversão do material do SBPqO-2015

Repositório comum da equipe de processamento do material. *Workflow* :

![alt text](https://github.com/ppKrauss/SBPqO-2015/blob/master/etc/imgs/diagrama1-workflow.png "Logo Title Text 1")

## Convenções

Nomes de pastas:

* [**`amostras`**](./amostras): arquivos de amostragem de material bruto para decisões e discussões na equipe do projeto. 

* **`entregas`**: arquivos "brutos" de cada uma das entregas, finais ou parciais. 

* **`src`**: código-fonte das ferramentas de uso comum da equipe.

* **`etc`**: outros materiais de uso geral, como a pasta `img` de imagens deste README.

Sintaxe dos nomes dos arquivos nas pastas `amostras` e `entregas`:&nbsp; `NOME-tXvY.ext.zip`, onde `NOME.ext` é o nome original do arquivo, e `X` e `Y` a numeração sequencial da versão (1.1, 1.2, 2.1, etc.) que é hieraquizada em *teste* (1 para primeira tentativa, 2 para a segunda, etc.) e *versão* (pequenas alterações de formato ou correções do arquivo). Exemplos: `AO-t1v1.html.zip`, o primeiro teste de dados brutos, `AO-t1v2.html.zip` uma versão convertida para UTF8 e acrescida de tags para encapsulamento. Quando a versão não requer backup (controle pelo git) pode-se dispensar o indicador de versão `vY`.

## Entregas
No *workflow* os tipos de pacotes entregáveis são rotulados, uma entrega consiste do depósito de um ou mais desses pacotes, seguida de aviso em e-mail. Se os pocates forem integralmente aceitos, o Trello é atualizado. A seguir as entregas acordadas até o momento (o status delas deve ser verificado no Trello).

**Entregas produtivas** contendo pacotes previstos pelo *workflow*:

* **EP1.1 (HTM1)**: primeiro lote de resumos, originais.

* **EP1.2 (HTM2)**: primeiro lote de resumos convertidos e normalizados.

* **EP1.3 (HTM3)**: primeiro lote de resumos revisados.

* **EP1.4 (CSV1-3)**: primeiro lote de planilhas (CSV1 a CSV3).

* **EP1.5 (XML1,PDF1)**: primeiro lote de materiais processados. A subdivisão de conteúdos em *XML1* é uma facilidade para tratar o [conteúdo-extra](https://github.com/ppKrauss/SBPqO-2015/wiki/Gloss%C3%A1rio).

* **EP2.1 (HTM1)**: ultima versão de resumos originais.

* **EP2.2 (HTM2)**: ultima versão de resumos convertidos e normalizados.

* **EP2.3 (HTM3)**: ultima versão de resumos revisados.

* **EP2.4 (CSV1-3)**: ultima versão de planilhas (CSV1 a CSV3).

* **EP2.5 (XML1,PDF1)**: ultima versão de materiais processados.

* **EP3.1 (HTM4)**: versão bruta do [Conteúdo-extra estático](https://github.com/ppKrauss/SBPqO-2015/wiki/Gloss%C3%A1rio). Isso inclui também o mapa do local do evento.
* **EP3.2 (XML2)**: versão convertida do do *conteúdo-extra estático*.

**Entregas secundárias**, não-produtivas, para ajustes, testes e discussões:

* **ES1** (amostra/AO):  amostra para discussão da formatação e dos prazos de revisão dos resumos. 

* **ES2** (amostra/{lote2014,indiceAutores,indiceDescritores,localHorario}): amostras para discussão dos prazos e acertos contratuais entre as partes do *workflow*. 

Os [contratos referentes às entregas encontram-se na Wiki](https://github.com/ppKrauss/SBPqO-2015/wiki).

##Subsídios externos
* http://www.SBPqO.org.br/ (cliente e evento relacionados ao presente projeto)
* [Nosso Trello](https://trello.com/b/ST4FS44Z/sbpqo-2015) (kanban das tarefas)
* http://www.Open-Contracting.org (referencial sobre transparência em contratos)
* [Contratos deste projeto](https://github.com/ppKrauss/SBPqO-2015/wiki) e [discussão geral de contratos em pequenos projetos](http://www.xmlfusion.org/wiki-do-mei/Contratos)

