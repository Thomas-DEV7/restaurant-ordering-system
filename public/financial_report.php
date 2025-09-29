<?php
// public/financial_report.php
$pageTitle = "Relatório Financeiro Diário";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

$currentPage = 'financial_report.php';
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-chart-line me-2"></i> Relatório Financeiro Diário</h1>
    <p class="page-subtitle">Dados de vendas e taxa de serviço fechados no dia de hoje.</p>

    <div id="financial-summary" class="row g-4 mt-4">
        <div class="col-12 text-center my-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3 text-muted">Buscando dados financeiros...</p>
        </div>
    </div>
    
    <div id="error-message" class="alert alert-danger d-none mt-4"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const summaryContainer = document.getElementById('financial-summary');
        const errorMessageDiv = document.getElementById('error-message');

        function formatCurrency(amount) {
            const num = parseFloat(amount || 0);
            return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function loadFinancialSummary() {
            errorMessageDiv.classList.add('d-none');
            summaryContainer.innerHTML = `
                <div class="col-12 text-center my-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3 text-muted">Atualizando dados...</p>
                </div>
            `;

            fetch('api.php?action=get_daily_summary')
                .then(response => {
                    if (!response.ok) throw new Error('Erro de rede ou servidor.');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderSummary(data.data);
                    } else {
                        errorMessageDiv.textContent = `Erro da API: ${data.message}`;
                        errorMessageDiv.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    errorMessageDiv.textContent = `Falha na comunicação com o servidor: ${error}`;
                    errorMessageDiv.classList.remove('d-none');
                    console.error('Erro ao buscar resumo financeiro:', error);
                });
        }

        function renderSummary(summary) {
            const totalDue = parseFloat(summary.total_10_percent_due || 0);
            const totalReceived = parseFloat(summary.total_10_percent_received || 0);
            const statusColor = totalReceived >= totalDue ? 'success' : 'warning';
            
            summaryContainer.innerHTML = `
                <div class="col-md-6 col-lg-3">
                    <div class="card p-3 text-center shadow">
                        <i class="fas fa-receipt fa-2x text-info mb-2"></i>
                        <p class="mb-1 text-muted">Comandas Fechadas</p>
                        <h3 class="fw-bold">${summary.total_orders_closed}</h3>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card p-3 text-center shadow">
                        <i class="fas fa-shopping-basket fa-2x text-primary mb-2"></i>
                        <p class="mb-1 text-muted">Vendas Brutas (Itens)</p>
                        <h3 class="fw-bold">${formatCurrency(summary.total_subtotal)}</h3>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card p-3 text-center shadow border-start border-4 border-${statusColor}">
                        <i class="fas fa-user-friends fa-2x text-success mb-2"></i>
                        <p class="mb-1 text-muted">Taxa de Serviço (Recebida)</p>
                        <h3 class="fw-bold">${formatCurrency(totalReceived)}</h3>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card p-3 text-center shadow bg-primary text-white">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <p class="mb-1">Total Faturado (Bruto + Taxa)</p>
                        <h3 class="fw-bold">${formatCurrency(summary.total_billed_amount)}</h3>
                    </div>
                </div>
            `;
        }

        loadFinancialSummary();
        // Atualiza automaticamente a cada 60 segundos
        setInterval(loadFinancialSummary, 60000); 
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>