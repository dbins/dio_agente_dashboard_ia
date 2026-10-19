# 🚗 Porsche Executive Sales Dashboard

Dashboard executivo de performance de vendas desenvolvido em **HTML5/JS único** com estilização baseada no **Porsche Design System** (estética minimalista, técnica, moderna e de alto contraste).

[Link Dashboard](https://dbins.github.io/dio_agente_dashboard_ia/)

---

## 📌 Resumo do Projeto

O objetivo deste projeto foi sanitizar, estruturar e transformar dados brutos de transações de veículos Porsche em um painel interativo de inteligência de negócios. 

Para manter o projeto leve, portátil e de fácil distribuição, todo o dashboard foi contido em um **único arquivo HTML** (`index.html`), eliminando a necessidade de servidores backend complexos para a sua execução local.

---

## 🎯 Perguntas de Negócio Respondidas

O dashboard foi construído para responder de forma imediata a três pilares indispensáveis de negócios:

1. **Modelo Mais Vendido:** Qual modelo/variante lidera em volume de vendas e apelo de mercado?
2. **Volume de Vendas por Ano:** Como se distribuem as vendas ao longo dos anos dos modelos (evolução temporal)?
3. **Métodos de Pagamento:** Qual a participação de cada modalidade de pagamento (Wire Transfer, Financing, Credit Card, Cash, etc.) no faturamento?

---

## 📊 Indicadores e Componentes Visuais

### 1. Indicadores de Topo (KPIs Dynamic Cards)
* **Receita Total ($):** Soma acumulada em USD dos veículos comercializados no período ou recorte selecionado.
* **Volume de Vendas:** Quantidade total de unidades negociadas.
* **Modelo Campeão:** Destaque visual do modelo mais vendido no filtro aplicado e seu respectivo volume.
* **Ticket Médio ($):** Valor médio investido por veículo comercializado.

### 2. Painel Gráfico Interativo (Chart.js)
* **Modelos Mais Vendidos (Bar Chart):** Ranking visual das linhas de veículos em destaque.
* **Volume de Vendas por Ano (Line Chart):** Linha de tendência mostrando o desempenho por ano do modelo/venda.
* **Distribuição por Pagamento (Doughnut Chart):** Divisão percentual dos métodos financeiros escolhidos pelos clientes.
* **Top Estados por Receita (Horizontal Bar Chart):** Regiões geográficas que mais geram faturamento.

### 3. Tabela de Transações e Filtros
* **Barra de Filtros Dinâmicos:** Permite filtrar instantaneamente por *Modelo*, *Ano*, *Método de Pagamento* e *Estado (UF)*.
* **Tabela de Detalhamento:** Lista com paginação/busca rápida contendo ID da venda, data, cliente, modelo, ano, valor, localização e status da entrega.

---

## 🛠️ Tecnologias Utilizadas

* **HTML5 & CSS3:** Layout estruturado com CSS Grid/Flexbox sob as diretrizes visuais do *Porsche Design System* (Cores: `#010205`, `#FFFFFF` e `#D5001C Porsche Carmine Red`).
* **JavaScript (Vanilla):** Tratamento, filtragem e agregação de dados dinâmicos no cliente.
* **Chart.js (via CDN):** Renderização de gráficos responsivos e interativos.
* **FontAwesome (via CDN):** Ícones minimalistas para interface técnica.

---

![Desafio DIO](dashboard.jpg)
