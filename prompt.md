Atue como um desenvolvedor Frontend e Designer UI/UX sênior.

Crie um Dashboard Executivo de Vendas em um ÚNICO ARQUIVO HTML (com CSS e JS embutidos), estilizado rigorosamente sob o Porsche Design System (estética limpa, técnica, luxuosa e minimalista, inspirada no site oficial da Porsche).

### 🎯 PERGUNTAS DE NEGÓCIO INDISPENSÁVEIS (Visualizações Principais):
O dashboard deve responder de forma clara e visual a:
1. Modelo mais vendido (gráfico de ranking/barras por volume de unidades e/ou destaque em card KPI).
2. Volume de vendas por ano (evolução ou distribuição do ano do modelo/venda).
3. Distribuição por Método de Pagamento (Wire Transfer, Credit Card, Financing, Cash, etc.).

### 🎛️ FILTROS INTERATIVOS DINÂMICOS:
O topo do dashboard deve conter uma barra de filtros funcionais que recalculam os KPIs, gráficos e tabelas em tempo real quando alterados:
- Filtro 1: Modelo do Veículo (ex: 911, Cayenne, Macan, Taycan, Panamera, 718, Todos)
- Filtro 2: Cidade / Estado (ex: CA, TX, AZ, FL, etc.)
- Filtro 3: Ano do Modelo / Venda (ex: 2020 a 2026)
- Filtro 4: Método de Pagamento (ex: Wire Transfer, Credit Card, Financing, etc.)
- Botão "Limpar Filtros" com estilo secundário elegante.

### 📈 INDICADORES DE TOPO (KPIs Indispensáveis e Complementares):
Cards no topo com valores grandes e tipografia destacada:
- Total de Vendas (Quantidade total de unidades) [Indispensável]
- Receita Total (Valor total acumulado em USD) [Indispensável]
- Ticket Médio por Veículo (Valor médio por transação) [Complementar]
- Modelo Campeão / Destaque (Modelo mais vendido no período filtrado) [Complementar]

### 🎨 DIRETRIZES DE DESIGN (Porsche Aesthetic):
1. PALETA E ESTILO VISUAL:
   - Fundo: Branco puro (#FFFFFF) ou cinza ultra-claro (#F4F4F4).
   - Cards: Fundo branco com borda fina de 1px (#E2E2E2) e cantos levemente retos/suavizados (border-radius: 0px a 4px).
   - Textos: Preto profundo (#111111) para títulos e valores; cinza neutro (#626669) para rótulos.
   - Cor de Acento (Porsche Red): #D5001C para destaques primários, barras ativas e badges.
2. GRÁFICOS (Chart.js via CDN):
   - Gráfico 1: Modelos Mais Vendidos (Gráfico de barras com destaque em Porsche Red).
   - Gráfico 2: Volume de Vendas por Ano (Gráfico de linhas ou colunas).
   - Gráfico 3: Participação por Método de Pagamento (Gráfico Doughnut/Rosca ou Pizza).
   - Gráfico 4 (Bônus): Top Cidades/Estados por Receita.
3. TABELA DE DADOS DETALHADA:
   - Tabela inferior listando os registros (ID, Data, Cliente, Modelo, Ano, Preço, Cidade/Estado, Método de Pagamento e Status) com filtro/busca e paginação.

### 💾 DADOS EMBUTIDOS E REQUISITOS TÉCNICOS:
- O script JavaScript deve conter todo o dataset extraído do arquivo `base_sanitizada.xlsx` para garantir a interatividade dos filtros sem dependências de servidores externos.
- Código completo entregue em um ÚNICO arquivo HTML funcional e responsivo.