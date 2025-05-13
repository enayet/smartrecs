/**
 * WooCommerce Product Recommendations Chart Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Chart variables
    let impressionsClicksChart, recommendationTypesChart, placementPerformanceChart;

    // Color functions
    function getColor(index, alpha = 1) {
        const colors = [
            'rgba(54, 162, 235, ' + alpha + ')',
            'rgba(255, 99, 132, ' + alpha + ')',
            'rgba(75, 192, 192, ' + alpha + ')',
            'rgba(255, 206, 86, ' + alpha + ')',
            'rgba(153, 102, 255, ' + alpha + ')',
            'rgba(255, 159, 64, ' + alpha + ')',
            'rgba(199, 199, 199, ' + alpha + ')',
            'rgba(83, 102, 255, ' + alpha + ')',
            'rgba(40, 180, 99, ' + alpha + ')',
            'rgba(210, 105, 30, ' + alpha + ')'
        ];
        
        return colors[index % colors.length];
    }

    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: woocommerce_admin_meta_boxes.currency
        }).format(amount);
    }

    // Update impressions chart
    function updateImpressionsChart(data) {
        const ctx = document.getElementById('impressions-clicks-chart').getContext('2d');
        const datasets = [];
        let totalImpressions = 0;
        
        // Create dataset for each recommendation type
        data.types.forEach(function(type, index) {
            datasets.push({
                label: type + ' ' + 'Impressions',
                data: data.data.map(function(item) {
                    const value = item[type] || 0;
                    totalImpressions += value;
                    return value;
                }),
                borderColor: getColor(index),
                backgroundColor: getColor(index, 0.1),
                borderWidth: 2,
                fill: true
            });
        });
        
        // Update total impressions in summary
        $('#total-impressions').text(totalImpressions.toLocaleString());
        
        // Destroy existing chart if it exists
        if (impressionsClicksChart) {
            impressionsClicksChart.destroy();
        }
        
        // Create new chart
        impressionsClicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.data.map(function(item) { return item.date; }),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Update clicks chart
    function updateClicksChart(data) {
        let totalClicks = 0;
        
        // Add click datasets to existing chart
        data.types.forEach(function(type, index) {
            impressionsClicksChart.data.datasets.push({
                label: type + ' ' + 'Clicks',
                data: data.data.map(function(item) {
                    let value = item[type] || 0;
                    totalClicks += value;
                    return value;
                }),
                borderColor: getColor(index + data.types.length),
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false
            });
        });
        
        // Update total clicks in summary
        $('#total-clicks').text(totalClicks.toLocaleString());
        
        // Calculate CTR
        let impressions = parseInt($('#total-impressions').text().replace(/,/g, ''));
        let ctr = impressions > 0 ? (totalClicks / impressions * 100).toFixed(2) + '%' : '0.00%';
        $('#total-ctr').text(ctr);
        
        // Update chart
        impressionsClicksChart.update();
    }

    // Update recommendation types chart
    function updateRecommendationTypesChart(data) {
        const ctx = document.getElementById('recommendation-types-chart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (recommendationTypesChart) {
            recommendationTypesChart.destroy();
        }
        
        // Create new chart
        recommendationTypesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(function(item) { return item.type; }),
                datasets: [
                    {
                        label: 'Click-Through Rate (%)',
                        data: data.map(function(item) { return item.ctr; }),
                        backgroundColor: data.map(function(item, index) { return getColor(index, 0.7); }),
                        borderColor: data.map(function(item, index) { return getColor(index); }),
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'CTR (%)'
                        }
                    }
                }
            }
        });
    }

    // Update placement chart
    function updatePlacementChart(data) {
        const ctx = document.getElementById('placement-performance-chart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (placementPerformanceChart) {
            placementPerformanceChart.destroy();
        }
        
        // Create new chart
        placementPerformanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(function(item) { return item.placement; }),
                datasets: [
                    {
                        label: 'Click-Through Rate (%)',
                        data: data.map(function(item) { return item.ctr; }),
                        backgroundColor: data.map(function(item, index) { return getColor(index + 5, 0.7); }),
                        borderColor: data.map(function(item, index) { return getColor(index + 5); }),
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'CTR (%)'
                        }
                    }
                }
            }
        });
    }

    // Update revenue summary
    function updateRevenueSummary(data) {
        let totalRevenue = 0;
        
        // Calculate total revenue
        data.forEach(function(item) {
            if (item.type !== 'Total') {
                totalRevenue += parseFloat(item.revenue);
            }
        });
        
        // Update total revenue in summary
        $('#total-revenue').text(formatCurrency(totalRevenue));
        
        // Update revenue table
        let tableHtml = '';
        data.forEach(function(item) {
            tableHtml += '<tr>';
            tableHtml += '<td>' + item.type + '</td>';
            tableHtml += '<td>' + formatCurrency(item.revenue) + '</td>';
            tableHtml += '<td>' + item.orders + '</td>';
            tableHtml += '</tr>';
        });
        
        $('#revenue-table tbody').html(tableHtml);
    }

    // Update top products
    function updateTopProducts(data) {
        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="4">No data available for this time period.</td></tr>';
        } else {
            data.forEach(function(product) {
                html += '<tr>';
                html += '<td>';
                if (product.thumbnail) {
                    html += '<img src="' + product.thumbnail + '" width="40" height="40" style="vertical-align: middle; margin-right: 10px;">';
                }
                html += '<a href="' + product.url + '" target="_blank">' + product.name + '</a></td>';
                html += '<td>' + product.clicks.toLocaleString() + '</td>';
                html += '<td>' + formatCurrency(product.price) + '</td>';
                html += '<td><a href="' + product.url + '" target="_blank" class="button button-small">View</a></td>';
                html += '</tr>';
            });
        }
        
        $('#top-products-table').html(html);
    }

    // Display test results
    function displayTestResults(data) {
        // Update test info
        $('#test-results-name').text(data.name);
        $('#test-results-description').text(data.description);
        
        // Format dates
        let dateText = '';
        if (data.start_date) {
            dateText += 'Started: ' + formatDate(data.start_date);
        }
        if (data.end_date) {
            dateText += ' | Ended: ' + formatDate(data.end_date);
        }
        $('#test-results-dates').text(dateText);
        
        // Update status
        let statusClass = data.active ? 'status-active' : (data.end_date ? 'status-completed' : 'status-inactive');
        let statusText = data.active ? 'Active' : (data.end_date ? 'Completed' : 'Inactive');
        $('#test-results-status').html('<span class="' + statusClass + '">' + statusText + '</span>');
        
        // Update variants table
        let tableHtml = '';
        let chartLabels = [];
        let impressionsData = [];
        let clickRateData = [];
        let conversionRateData = [];
        
        // Find winner
        let winner = null;
        let highestCTR = 0;
        
        data.variants.forEach(function(variant) {
            chartLabels.push(variant.name);
            
            // Get clicks and CTR
            let clicks = variant.conversions.click ? variant.conversions.click.count : 0;
            let clickRate = variant.conversions.click ? variant.conversions.click.rate : 0;
            clickRateData.push(clickRate);
            
            // Get purchases and conversion rate
            let purchases = variant.conversions.purchase ? variant.conversions.purchase.count : 0;
            let conversionRate = purchases > 0 && variant.impressions > 0 ? (purchases / variant.impressions) * 100 : 0;
            conversionRateData.push(conversionRate);
            
            // Track impressions for chart
            impressionsData.push(variant.impressions);
            
            // Check if this is the winner
            if (clickRate > highestCTR) {
                highestCTR = clickRate;
                winner = variant;
            }
            
            // Add row to table
            tableHtml += '<tr>';
            tableHtml += '<td>' + variant.name + '</td>';
            tableHtml += '<td>' + getAlgorithmLabel(variant.type) + '</td>';
            tableHtml += '<td>' + variant.impressions.toLocaleString() + '</td>';
            tableHtml += '<td>' + clicks.toLocaleString() + '</td>';
            tableHtml += '<td>' + clickRate.toFixed(2) + '%</td>';
            tableHtml += '<td>' + purchases.toLocaleString() + '</td>';
            tableHtml += '<td>' + conversionRate.toFixed(2) + '%</td>';
            tableHtml += '</tr>';
        });
        
        $('#test-results-table-body').html(tableHtml);
        
        // Update summary
        let summaryText = '';
        if (data.variants.length > 0) {
            summaryText = 'This test compares ' + data.variants.length + ' different recommendation algorithms. ';
            
            if (data.active) {
                summaryText += 'The test is currently running.';
            } else if (data.end_date) {
                summaryText += 'The test has completed.';
            } else {
                summaryText += 'The test is inactive.';
            }
        }
        $('#test-summary-text').text(summaryText);
        
        // Show winner if test is complete
        if (data.end_date && winner) {
            $('#test-winner').text(winner.name + ' (' + getAlgorithmLabel(winner.type) + ')');
            
            let improvementText = '';
            if (data.variants.length > 1) {
                // Find the baseline variant
                let baseline = data.variants[0];
                let baselineCTR = baseline.conversions.click ? baseline.conversions.click.rate : 0;
                
                if (baselineCTR > 0 && winner.id !== baseline.id) {
                    let improvement = ((highestCTR - baselineCTR) / baselineCTR) * 100;
                    improvementText = 'This variant outperformed the baseline by ' + improvement.toFixed(2) + '%.';
                }
            }
            
            $('#test-winner-description').text(improvementText);
            $('#test-winner-container').show();
        } else {
            $('#test-winner-container').hide();
        }
        
        // Create/update chart
        updateResultsChart(chartLabels, impressionsData, clickRateData, conversionRateData);
    }

    // Update test results chart
    function updateResultsChart(labels, impressions, clickRates, conversionRates) {
        const ctx = document.getElementById('test-results-chart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (window.testResultsChart) {
            window.testResultsChart.destroy();
        }
        
        // Create new chart
        window.testResultsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Click-Through Rate (%)',
                        data: clickRates,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Conversion Rate (%)',
                        data: conversionRates,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Impressions',
                        data: impressions,
                        backgroundColor: 'rgba(75, 192, 192, 0.3)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        type: 'line',
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
                        title: {
                            display: true,
                            text: 'Rate (%)'
                        },
                        type: 'linear',
                        position: 'left'
                    },
                    y1: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Impressions'
                        },
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Get algorithm label
    function getAlgorithmLabel(type) {
        const labels = {
            'frequently_bought': 'Frequently Bought Together',
            'also_viewed': 'Customers Also Viewed',
            'similar': 'Similar Products',
            'personalized': 'Personalized Recommendations',
            'enhanced': 'Enhanced Recommendations (ML)',
            'seasonal': 'Seasonal Products',
            'trending': 'Trending Products',
            'ai_hybrid': 'AI Hybrid',
            'context_aware': 'Context-Aware',
            'custom': 'Custom Products'
        };
        
        return labels[type] || type;
    }

    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Export functions to global scope for use in admin.js
    window.wc_recommendations_charts = {
        updateImpressionsChart: updateImpressionsChart,
        updateClicksChart: updateClicksChart,
        updateRecommendationTypesChart: updateRecommendationTypesChart,
        updatePlacementChart: updatePlacementChart,
        updateRevenueSummary: updateRevenueSummary,
        updateTopProducts: updateTopProducts,
        displayTestResults: displayTestResults
    };

})(jQuery);