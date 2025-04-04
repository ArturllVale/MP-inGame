<?php
session_start();

// Verificar se o usuário está logado e tem permissão de administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
    header('Location: index.php');
    exit;
}

require_once 'class/Conn.class.php';
$pdo = DB::getInstance();

// Buscar dados de pagamentos para o dashboard
$paymentLogsQuery = $pdo->query("SELECT * FROM payment_data_log ORDER BY data_registro DESC");
$paymentLogs = $paymentLogsQuery->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas gerais
$totalPaymentsQuery = $pdo->query("SELECT COUNT(*) as total FROM payment_data_log");
$totalPayments = $totalPaymentsQuery->fetch(PDO::FETCH_ASSOC)['total'];

$totalCashQuery = $pdo->query("SELECT SUM(cash) as total FROM payment_data_log");
$totalCash = $totalCashQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$pendingPaymentsQuery = $pdo->query("SELECT COUNT(*) as total FROM payment_data_log WHERE retirado = 0");
$pendingPayments = $pendingPaymentsQuery->fetch(PDO::FETCH_ASSOC)['total'];

$completedPaymentsQuery = $pdo->query("SELECT COUNT(*) as total FROM payment_data_log WHERE retirado = 1");
$completedPayments = $completedPaymentsQuery->fetch(PDO::FETCH_ASSOC)['total'];

// Buscar dados para o gráfico de pagamentos por dia (últimos 7 dias)
$dailyStatsQuery = $pdo->query("SELECT DATE(data_registro) as date, COUNT(*) as count, SUM(cash) as total 
                               FROM payment_data_log 
                               WHERE data_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                               GROUP BY DATE(data_registro) 
                               ORDER BY date ASC");
$dailyStats = $dailyStatsQuery->fetchAll(PDO::FETCH_ASSOC);

// Preparar dados para o gráfico
$chartDates = [];
$chartCounts = [];
$chartTotals = [];

foreach ($dailyStats as $stat) {
    $chartDates[] = date('d/m', strtotime($stat['date']));
    $chartCounts[] = $stat['count'];
    $chartTotals[] = $stat['total'];
}

// Converter para JSON para uso no JavaScript
$chartDatesJSON = json_encode($chartDates);
$chartCountsJSON = json_encode($chartCounts);
$chartTotalsJSON = json_encode($chartTotals);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Pagamentos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex flex-col h-screen">
        <!-- Navbar superior -->
        <nav class="bg-blue-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold">Painel Administrativo</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="hidden md:inline">Bem-vindo, <?php echo htmlspecialchars($_SESSION['userid']); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-sign-out-alt mr-2"></i><span class="hidden md:inline">Sair</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Conteúdo principal -->
        <div class="flex-grow overflow-auto">
            <div class="container mx-auto px-4 py-6">
                <!-- Cards de estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                <i class="fas fa-money-bill-wave text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total de Pagamentos</p>
                                <p class="text-2xl font-bold"><?php echo number_format($totalPayments, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                <i class="fas fa-coins text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total de Cash</p>
                                <p class="text-2xl font-bold"><?php echo number_format($totalCash, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Pagamentos Pendentes</p>
                                <p class="text-2xl font-bold"><?php echo number_format($pendingPayments, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                                <i class="fas fa-check-circle text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Pagamentos Concluídos</p>
                                <p class="text-2xl font-bold"><?php echo number_format($completedPayments, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-4">Pagamentos dos Últimos 7 Dias</h2>
                        <div class="h-80">
                            <canvas id="paymentsChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-4">Distribuição de Status</h2>
                        <div class="h-80">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabela de logs de pagamentos -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-xl font-bold">Logs de Pagamentos</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cash</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($paymentLogs) > 0): ?>
                                    <?php foreach ($paymentLogs as $log): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $log['log_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $log['acc_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($log['cash'], 0, ',', '.'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if ($log['retirado']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Retirado</span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i:s', strtotime($log['data_registro'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum registro encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4">
            <div class="container mx-auto px-4 text-center text-gray-600 text-sm">
                &copy; <?php echo date('Y'); ?> - Sistema de Administração de Pagamentos
            </div>
        </footer>
    </div>

    <script>
        // Gráfico de pagamentos dos últimos 7 dias
        const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
        const paymentsChart = new Chart(paymentsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $chartDatesJSON; ?>,
                datasets: [
                    {
                        label: 'Quantidade de Pagamentos',
                        data: <?php echo $chartCountsJSON; ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Total de Cash',
                        data: <?php echo $chartTotalsJSON; ?>,
                        type: 'line',
                        fill: false,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Quantidade'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Cash'
                        }
                    }
                }
            }
        });

        // Gráfico de distribuição de status
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pendentes', 'Retirados'],
                datasets: [{
                    data: [<?php echo $pendingPayments; ?>, <?php echo $completedPayments; ?>],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderColor: [
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>