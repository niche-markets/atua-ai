'use strict';

import ApexCharts from 'apexcharts'

function createSuperset(dataset) {
    if (dataset.length === 0) {
        const today = new Date();
        const start = new Date(today.getFullYear(), today.getMonth(), 2);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        return fillDates(start, end);
    }

    // Sort the dataset by date
    dataset.sort((a, b) => new Date(a.category) - new Date(b.category));

    // Determine the range for filling
    const startDate = new Date(dataset[0].category);
    const endDate = new Date(dataset[dataset.length - 1].category);
    const start = new Date(startDate.getFullYear(), startDate.getMonth(), 2);
    const end = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 1);

    return fillDates(start, end, dataset);
}

function fillDates(start, end, dataset = []) {
    const dateMap = new Map(dataset.map(item => [item.category, item.value]));
    const result = [];
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        result.push({
            category: dateStr,
            value: dateMap.has(dateStr) ? dateMap.get(dateStr) : 0
        });
    }
    return result;
}

function color(name) {
    return `rgb(${getComputedStyle(document.documentElement).getPropertyValue(name).split(' ').join(', ')})`;
}

export class ChartElement extends HTMLElement {
    static observedAttributes = [
        'set'
    ];

    constructor() {
        super();

        this.container = this.querySelector('[chart]') || this;
    }


    disconnectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    getReadableDuration(duration) {
        let date = new Date(0);
        date.setSeconds(duration);

        if (duration > 3600) {
            return date.toISOString().substring(11, 19)
        }

        return date.toISOString().substring(14, 19)
    }

    seekTo(duration = 0) {
        this.wave.seekTo(duration / this.wave.getDuration());
    }

    render() {
        let set = createSuperset(JSON.parse(this.getAttribute('set')));

        let chart = new ApexCharts(this.container, {
            series: [
                {
                    data: set.map(row => {
                        return {
                            x: row.category + ' GMT',
                            y: row.value
                        }
                    })
                }
            ],
            chart: {
                type: 'bar',
                height: '100%',
                fontFamily: 'inherit',
                foreColor: color('--color-content-dimmed'),
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                }
            },
            colors: [
                color('--color-content')
            ],
            grid: {
                show: false,
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    borderRadiusApplication: 'end',
                    columnWidth: '95%',
                }
            },
            xaxis: {
                type: "datetime",
                labels: {
                    style: {
                        fontFamily: 'inherit',
                        cssClass: 'text-xs',
                    },
                },
            },
            yaxis: {
                labels: {
                    style: {
                        fontFamily: 'inherit',
                        cssClass: 'text-xs',
                    },
                    formatter: (value) => {
                        let lang = document.documentElement.lang || 'en';
                        let amount = parseFloat(value);

                        let options = {
                            style: 'decimal',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0,
                            trailingZeroDisplay: 'stripIfInteger'
                        };

                        let formatter = new Intl.NumberFormat(lang, options);
                        let text = formatter.format(amount);

                        if (text.length >= 5) {
                            formatter = new Intl.NumberFormat(lang, { ...options, notation: 'compact', compactDisplay: 'short' });
                            text = formatter.format(amount);
                        }

                        return text;
                    },
                },
            },
            stroke: {
                show: true,
                width: 2,
            },
            dataLabels: {
                enabled: false,
            },
            tooltip: {
                custom: ({ series, seriesIndex, dataPointIndex, w }) => {
                    let date = new Date(w.globals.seriesX[seriesIndex][dataPointIndex]);
                    let lang = document.documentElement.lang || 'en';

                    let formatter = new Intl.DateTimeFormat(lang, {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });

                    return (
                        '<div class="badge">' +
                        "<span class='font-bold'>" +
                        formatter.format(date) +
                        ": " +
                        "</span>" +
                        series[seriesIndex][dataPointIndex] +
                        "</div>"
                    );
                }
            }
        });
        chart.render();
    }
}