// Mileage Chart Implementation using Chart.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('mileageChart');
    
    if (ctx) {
        // Create gradient for the chart
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)');
        gradient.addColorStop(0.5, 'rgba(54, 162, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0.1)');
        
        // Chart data based on the provided table
        const chartData = {
            labels: ['Feb 2019', 'Nov 2020', 'Sep 2021', 'Jan 2022', 'Aug 2022', 'Sep 2022', 'Jan 2023'],
            datasets: [{
                label: 'Mileage',
                data: [35409, 38413, 39003, 39454, 47003, 46113, 49413],
                borderColor: '#36a2eb',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#36a2eb',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#36a2eb',
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
                                return context.parsed.y.toLocaleString() + ' miles';
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
                        min: 32000,
                        max: 52000,
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
                                return (value / 1000) + 'k';
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
});

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