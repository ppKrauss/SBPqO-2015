# SBPqO-2015
Conversão do material do SBPqO-2015

Repositório comum da equipe de processamento do material. *Workflow* :

![alt text](https://github.com/ppKrauss/SBPqO-2015/blob/master/etc/imgs/diagrama1-workflow.png "Logo Title Text 1")

## Convenções

Nomes de pastas:

* [**`amostras`**](./amostras): arquivos de amostragem de material bruto para decisões e discussões na equipe do projeto.

* [**`entregas`**](./entregas): arquivos "brutos" de cada uma das entregas, finais ou parciais. 

* [**`src`**](./src): código-fonte das ferramentas de uso comum da equipe.

* [**`etc`**](./etc): outros materiais de uso geral, como a pasta `img` de imagens deste README.

Ver  [mais detalhes nas convenções de sintaxe](https://github.com/ppKrauss/SBPqO-2015/wiki/Gloss%C3%A1rio-e-conven%C3%A7%C3%B5es).

## Entregas
No *workflow* os tipos de pacotes entregáveis são rotulados, uma entrega consiste do depósito de um ou mais desses pacotes, seguida de aviso em e-mail. Se os pocates forem integralmente aceitos, o Trello é atualizado. Apenas a entrega tipo *XML2* não se encontra ilustrada.  

A seguir as entregas acordadas até o momento (o status delas deve ser verificado no Trello).

**Entregas produtivas** contendo pacotes previstos pelo *workflow*:

* Resumos e conteúdos vinculados:

 * **EP1.1 (HTM1)**: primeiro lote de resumos, originais.

 * **EP1.2 (HTM2)**: primeiro lote de resumos convertidos e normalizados.

 * **EP1.4 (CSV1-3)**: primeiro lote de planilhas (CSV1 a CSV3).

 * **EP1.5 (XML1,PDF1)**: primeiro lote de materiais processados. A subdivisão de conteúdos em *XML1* é uma facilidade para tratar o [conteúdo-extra](https://github.com/ppKrauss/SBPqO-2015/wiki/Gloss%C3%A1rio-e-conven%C3%A7%C3%B5es).

 * **EP2.1 (HTM1)**: ultima versão de resumos originais.

 * **EP2.4 (CSV1-3)**: ultima versão de planilhas (CSV1 a CSV3).

 * **EP2.5 (XML1,PDF1)**: ultima versão de materiais processados.
  
* [Conteúdo-extra independente](https://github.com/ppKrauss/SBPqO-2015/wiki/Gloss%C3%A1rio-e-conven%C3%A7%C3%B5es):

 * **EP3.1 (HTM5)**: versão bruta dos diversos conteúdos e imagens, que inclui também o mapa do local do evento.

 * **EP3.2 (CSV4-14)**: planilhas (CSV4 a CSV14).

 * **EP3.3 (XML2)**: versão convertida do *conteúdo-extra independente*.

**Entregas secundárias**, não-produtivas, para ajustes, testes e discussões:

* **ES1** (amostra/AO):  amostra para discussão da formatação e dos prazos de revisão dos resumos. 

* **ES2** (amostra/{lote2014,indiceAutores,indiceDescritores,localHorario}): amostras para discussão dos prazos e acertos contratuais entre as partes do *workflow*. 

Os [contratos referentes às entregas encontram-se na Wiki](https://github.com/ppKrauss/SBPqO-2015/wiki).

Sobre *EP2.2 (HTM2)* e *EP2.3 (HTM3)*, e demais resumos convertidos e normalizados. As entregas entre Peter e Cristina serão ignoradas, visto que HTM2 e HTM3 serão praticamente idênticos a HTM1, o processo será apenas uma *homologação* de segurança. Optou-se também por realizar uma breve revisão e homologação sobre CSV1 (em `indiceAutores`) para normalização dos nomes. O HTM4 será mantido apenas como *dump* para eventuais verificações de bug na geração do XML. Concluindo: a entrega EP2.2 não será verificada, o que de fato terá valor de entregável é *EP2.5*.

##Subsídios externos
* http://www.SBPqO.org.br/ (cliente e evento relacionados ao presente projeto)
* [Nosso Trello](https://trello.com/b/ST4FS44Z/sbpqo-2015) (kanban das tarefas)
* http://www.Open-Contracting.org (referencial sobre transparência em contratos)
* [Contratos deste projeto](https://github.com/ppKrauss/SBPqO-2015/wiki) e [discussão geral de contratos em pequenos projetos](http://www.xmlfusion.org/wiki-do-mei/Contratos)

