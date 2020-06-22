
    <h5 class="text-white">Gráfica
     Mobil {{$gasolina}}</h5>
    <canvas id="{{$gasolina.$terminal}}"></canvas>
    <h6 class="text-white">Dias Transcurridos</h6>

@push('js')
<script>
    var ctx = document.getElementById('{{$gasolina.$terminal}}').getContext('2d');
    Chart.defaults.global.defaultFontColor = 'white';
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            // Fechas 1-30,31,29
            labels: @json($fechas),
            // Informacion de los competidores
            datasets: [{
                    // Informacion del competidor Valero

                    // Nombre del competidor Valero
                    label: 'Mobil',
                    // Valores en la columna y (precios)
                    data: @json($vector_precio_valero),
                    //Color de fondo representativo de la competencia
                    backgroundColor: ['rgb(255, 255, 255,0)'],
                    // Color de borde de la competencia
                    borderColor: ['rgb(0, 191, 255)'],
                    // Tamaño de del borde
                    borderWidth: 2,
                }
            ]
        },
        options: {
            title: {
                fontColor: 'white'
            },
            legend: {
                labels: {
                    // This more specific font property overrides the global property
                    fontColor: 'white'
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: false,
                        stepSize: 0.5
                    }
                }],
            }
        }
    });

</script>
@endpush
