// CO2 Emissions Rating Chart using Chart.js

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library is not loaded');
        return;
    }

    const canvas = document.getElementById('co2RatingChart');
    if (!canvas) {
        console.error('CO2 Rating Chart canvas not found');
        return;
    }

    const ctx = canvas.getContext('2d');
    
    // CO2 Rating data
    const ratingData = {
        labels: ['A (0-100)', 'B-C (101-120)', 'D-E (121-140)', 'F-G (141-165)', 'H-I (166-185)', 'J-K (186-225)', 'L-M (225+)'],
        datasets: [{
            label: 'CO2 Emissions Rating',
            data: [100, 120, 140, 165, 185, 225, 250], // Max values for each range
            backgroundColor: [
                '#2E7D32', // A - Dark Green
                '#66BB6A', // B-C - Light Green
                '#9CCC65', // D-E - Light Green
                '#FFA726', // F-G - Orange
                '#FF7043', // H-I - Orange-Red
                '#E53935', // J-K - Red (current rating)
                '#B71C1C'  // L-M - Dark Red
            ],
            borderColor: [
                '#1B5E20',
                '#4CAF50',
                '#8BC34A',
                '#FF9800',
                '#FF5722',
                '#D32F2F',
                '#8B0000'
            ],
            borderWidth: 2,
            borderRadius: {
                topRight: 0,
                bottomRight: 0
            }
        }]
    };

    // Chart configuration
    const config = {
        type: 'bar',
        data: ratingData,
        options: {
            indexAxis: 'y', // Horizontal bars
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 10,
                    right: 40
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#333',
                    borderWidth: 1,
                    cornerRadius: 0,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            const ranges = [
                                '0 < 101 g/km',
                                '101 - 120 g/km',
                                '121 - 140 g/km',
                                '141 - 165 g/km',
                                '166 - 185 g/km',
                                '186 - 225 g/km',
                                '225+ g/km'
                            ];
                            return ranges[context.dataIndex];
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: false,
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#333',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        callback: function(value, index) {
                            const letters = ['A', 'B\nC', 'D\nE', 'F\nG', 'H\nI', 'J\nK', 'L\nM'];
                            return letters[index];
                        }
                    }
                }
            },
            elements: {
                bar: {
                    borderSkipped: false
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    };

    // Create the chart
    const co2Chart = new Chart(ctx, config);

    // Highlight current rating (K) after chart is rendered
    setTimeout(() => {
        // Add current rating indicator
        const currentRatingIndex = 5; // J-K rating
        const meta = co2Chart.getDatasetMeta(0);
        const bar = meta.data[currentRatingIndex];
        
        // Add visual indicator for current rating
        if (bar) {
            const canvasPosition = Chart.helpers.getRelativePosition(bar, co2Chart);
            const indicator = document.createElement('div');
            indicator.className = 'current-rating-indicator';
            indicator.textContent = 'K';
            indicator.style.cssText = `
                position: absolute;
                right: -30px;
                top: 50%;
                transform: translateY(-50%);
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 3px;
                font-weight: bold;
                font-size: 14px;
                z-index: 10;
            `;
            
            // Position indicator relative to the canvas
            const canvasRect = canvas.getBoundingClientRect();
            const chartArea = co2Chart.chartArea;
            const barY = chartArea.top + (bar.y - chartArea.top);
            
            indicator.style.top = barY + 'px';
            indicator.style.left = (canvasRect.width - 30) + 'px';
            
            canvas.parentElement.style.position = 'relative';
            canvas.parentElement.appendChild(indicator);
        }
    }, 1100);

    // Resize handler
    window.addEventListener('resize', function() {
        co2Chart.resize();
    });
});

// Fallback: Load Chart.js if not already present
if (typeof Chart === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = function() {
        console.log('Chart.js loaded successfully');
    };
    document.head.appendChild(script);
}