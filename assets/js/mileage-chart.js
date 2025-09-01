// Mileage Chart Implementation using Chart.js
function initializeMileageChart() {
    const ctx = document.getElementById('mileageChart');
    
    if (ctx) {
        // Проверяем наличие данных
        if (!window.mileageChartData || !window.mileageChartData.labels || window.mileageChartData.labels.length === 0) {
            // Скрываем canvas и показываем сообщение об отсутствии данных
            ctx.style.display = 'none';
            const noDataMsg = document.createElement('div');
            noDataMsg.className = 'no-chart-data';
            noDataMsg.innerHTML = '<p>No mileage data available for chart display.</p>';
            noDataMsg.style.textAlign = 'center';
            noDataMsg.style.padding = '20px';
            noDataMsg.style.color = '#666';
            ctx.parentNode.appendChild(noDataMsg);
            return;
        }
        
        // Create gradient for the chart
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)');
        gradient.addColorStop(0.5, 'rgba(54, 162, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0.1)');
        
        // Создаем массивы цветов для точек (красные для аномалий)
        const pointColors = window.mileageChartData.mileage.map((value, index) => {
            return window.mileageChartData.anomalies[index] ? '#ff6b6b' : '#36a2eb';
        });
        
        const pointBackgroundColors = window.mileageChartData.mileage.map((value, index) => {
            return window.mileageChartData.anomalies[index] ? '#ffffff' : '#ffffff';
        });
        
        // Используем динамические данные из PHP
        const chartData = {
            labels: window.mileageChartData.labels,
            datasets: [{
                label: 'Mileage',
                data: window.mileageChartData.mileage,
                borderColor: '#36a2eb',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: pointBackgroundColors,
                pointBorderColor: pointColors,
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: pointColors,
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        };

        // Chart configuration
        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 20,
                        left: 20
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(54, 162, 235, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#36a2eb',
                        borderWidth: 2,
                        cornerRadius: 0,
                        displayColors: false,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const isAnomaly = window.mileageChartData.anomalies[context.dataIndex];
                                let label = 'Mileage: ' + context.parsed.y.toLocaleString() + ' miles';
                                if (isAnomaly) {
                                    label += ' (Anomaly detected)';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.08)',
                            lineWidth: 1
                        },
                        border: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.2)'
                        },
                        ticks: {
                            color: '#666666',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            padding: 10
                        }
                    },
                    y: {
                        beginAtZero: false,
                        min: (() => {
                            const minValue = Math.min(...window.mileageChartData.mileage);
                            return Math.max(0, minValue - (minValue * 0.1)); // 10% отступ снизу
                        })(),
                        max: (() => {
                            const maxValue = Math.max(...window.mileageChartData.mileage);
                            return maxValue + (maxValue * 0.1); // 10% отступ сверху
                        })(),
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.08)',
                            lineWidth: 1
                        },
                        border: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.2)'
                        },
                        ticks: {
                            color: '#666666',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            padding: 10,
                            callback: function(value) {
                                // Форматируем значения как на изображении (32.5k, 35k, etc.)
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(value % 1000 === 0 ? 0 : 1) + 'k';
                                }
                                return value.toString();
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    line: {
                        borderJoinStyle: 'miter',
                        borderCapStyle: 'butt'
                    },
                    point: {
                        hoverBorderWidth: 4
                    }
                }
            }
        };

        // Create the chart
        new Chart(ctx, config);
    }
}

// Initialize chart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeMileageChart();
});

// Make function globally available for calling after AJAX data load
window.initializeMileageChart = initializeMileageChart;

// Load Chart.js library if not already loaded
if (typeof Chart === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = function() {
        // Re-run the chart initialization after Chart.js loads
        const event = new Event('DOMContentLoaded');
        document.dispatchEvent(event);
    };
    document.head.appendChild(script);
}