<?php
// public/financial_report.php
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

$currentPage = 'financial_report.php';
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-chart-line me-2"></i> Relatório Financeiro Diário</h1>
    <p class="page-subtitle">Dados de vendas e taxa de serviço fechados no dia de hoje.</p>

    <div class="row g-4 mb-5">
        
        <div class="col-md-6 col-lg-3">
            <div class="card p-3 shadow-sm text-center border-bottom border-4 border-info h-100">
                <i class="fas fa-receipt fa-2x text-info mb-2"></i>
                <p class="mb-0 text-muted">Comandas Fechadas</p>
                <h3 class="fw-bold text-info" id="comandas-fechadas">0</h3>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card p-3 shadow-sm text-center border-bottom border-4 border-warning h-100">
                <i class="fas fa-shopping-basket fa-2x text-warning mb-2"></i>
                <p class="mb-0 text-muted">Vendas Brutas (Itens)</p>
                <h3 class="fw-bold text-warning" id="vendas-brutas">R$ 0,00</h3>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card p-3 shadow-sm text-center border-bottom border-4 border-success h-100">
                <i class="fas fa-hand-holding-usd fa-2x text-success mb-2"></i>
                <p class="mb-0 text-muted">Taxa de Serviço (Recebida)</p>
                <h3 class="fw-bold text-success" id="taxa-servico">R$ 0,00</h3>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card p-3 shadow-sm text-center border-bottom border-4 border-primary h-100">
                <i class="fas fa-dollar-sign fa-2x text-primary mb-2"></i>
                <p class="mb-0 text-muted">Total Faturado (Bruto + Taxa)</p>
                <h3 class="fw-bold text-primary" id="total-faturado">R$ 0,00</h3>
            </div>
        </div>
    </div>

    <div class="text-center text-muted p-4 border rounded">
        <p id="loading-status">Carregando dados financeiros...</p>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function formatCurrency(amount) {
            const num = parseFloat(amount || 0);
            return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function loadFinancialReport() {
            const statusElement = document.getElementById('loading-status');
            statusElement.textContent = 'Carregando dados...';

            fetch('api.php?action=get_financial_report')
                .then(response => {
                    if (!response.ok) throw new Error('Falha de rede ou API');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        const { comandas_fechadas, vendas_brutas, taxa_servico, total_faturado } = data.data;

                        // Popula os Cards
                        document.getElementById('comandas-fechadas').textContent = comandas_fechadas;
                        document.getElementById('vendas-brutas').textContent = formatCurrency(vendas_brutas);
                        document.getElementById('taxa-servico').textContent = formatCurrency(taxa_servico);
                        document.getElementById('total-faturado').textContent = formatCurrency(total_faturado);

                        statusElement.textContent = `Relatório atualizado em ${new Date().toLocaleTimeString('pt-BR')}`;
                        statusElement.classList.add('text-success');

                    } else {
                        console.error("Erro ao carregar relatório:", data.message);
                        statusElement.textContent = "Erro ao carregar relatório. Verifique os dados no banco.";
                        statusElement.classList.add('text-danger');
                    }
                })
                .catch(error => {
                    console.error('Falha na comunicação com a API:', error);
                    statusElement.textContent = "Erro fatal de conexão com o servidor.";
                    statusElement.classList.add('text-danger');
                });
        }

        // Inicializa o Relatório
        loadFinancialReport();
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>