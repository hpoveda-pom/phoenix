<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="mb-3">
	<label for="periodSelector" class="form-label">Seleccionar período</label>
	<select id="periodSelector" class="form-select">
		<option value="1CO25">1CO25</option>
		<option value="3CO24">3CO24</option>
		<option value="2CO24">2CO24</option>
		<option value="1CO24">1CO24</option>
	</select>
</div>


<?php 
$array_charts = array(
	'donutChartGradient',
	'horizontalBarChart',
	'verticalBarChartGradient',
	'stackedAreaChart',
	'gradientLineChart',
	'combinedBarLineChart',
);
?>

<div class="row g-4 mb-9">
	<?php foreach ($array_charts as $key_charts => $row_charts) { ?>
		<div class="col-xl-6">
			<div class="card shadow-none border" data-component-card="data-component-card">
				<div class="card-header p-4 border-bottom bg-body">
					<div class="row g-3 justify-content-between align-items-center">
						<div class="col-12 col-md">
							<h4 class="text-body mb-0" data-anchor="data-anchor" id="basic-line-chart"><?php echo $row_charts; ?><a class="anchorjs-link " aria-label="Anchor" data-anchorjs-icon="#" href="#basic-line-chart" style="margin-left: 0.1875em; padding-right: 0.1875em; padding-left: 0.1875em;"></a></h4>
						</div>
						<div class="col col-md-auto">
							info
						</div>
					</div>
				</div>
				<div class="card-body p-3">
					<div id="<?php echo $row_charts ?>"></div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>

<script>
	document.addEventListener('DOMContentLoaded', () => {
  // Donut Chart con Gradiente
		const donutChartGradient = new ApexCharts(document.querySelector("#donutChartGradient"), {
			series: [],
			chart: { type: 'donut', height: 400 },
			title: { text: "Donut Chart con Gradiente" },
			colors: ['#00E396', '#FEB019', '#FF4560', '#775DD0'],
			fill: {
				type: 'gradient',
				gradient: {
					shade: 'light',
					type: "vertical",
					shadeIntensity: 0.7,
					gradientToColors: ['#87D4F9', '#FDD835', '#FF7D7D', '#A597DD'],
					stops: [0, 100]
				}
			},
			labels: ["Matrículas", "Prematrículas"]
		});
		donutChartGradient.render();

  // Bar Chart Horizontal
		const horizontalBarChart = new ApexCharts(document.querySelector("#horizontalBarChart"), {
			series: [{ data: [] }],
			chart: { type: 'bar', height: 400 },
			plotOptions: {
				bar: {
					horizontal: true,
					barHeight: '50%'
				}
			},
			title: { text: "Bar Chart Horizontal" },
			xaxis: { categories: [] }
		});
		horizontalBarChart.render();

  // Vertical Bar Chart con Gradiente
		const verticalBarChartGradient = new ApexCharts(document.querySelector("#verticalBarChartGradient"), {
			series: [{ name: "Matrículas", data: [] }, { name: "Prematrículas", data: [] }],
			chart: { type: 'bar', height: 400 },
			title: { text: "Bar Chart Vertical con Gradiente" },
			colors: ['#1E90FF', '#FF6347'],
			fill: {
				type: 'gradient',
				gradient: {
					shade: 'dark',
					type: "vertical",
					gradientToColors: ['#87CEEB', '#FF7F50'],
					stops: [0, 100]
				}
			},
			xaxis: { categories: [] }
		});
		verticalBarChartGradient.render();

  // Stacked Area Chart
		const stackedAreaChart = new ApexCharts(document.querySelector("#stackedAreaChart"), {
			series: [
				{ name: "Matrículas", data: [] },
				{ name: "Prematrículas", data: [] }
				],
			chart: { type: 'area', height: 400, stacked: true },
			title: { text: "Stacked Area Chart" },
			xaxis: { categories: [] },
			fill: { type: 'gradient' },
			colors: ['#008FFB', '#00E396']
		});
		stackedAreaChart.render();

  // Línea con Gradiente
		const gradientLineChart = new ApexCharts(document.querySelector("#gradientLineChart"), {
			series: [
				{ name: "Matrículas", data: [] },
				{ name: "Prematrículas", data: [] }
				],
			chart: { type: 'line', height: 400 },
			title: { text: "Gráfico de Línea con Gradiente" },
			stroke: { curve: 'smooth', width: 3 },
			fill: {
				type: 'gradient',
				gradient: {
					shade: 'light',
					type: "horizontal",
					gradientToColors: ['#00E396', '#FEB019'],
					stops: [0, 100]
				}
			},
			xaxis: { categories: [] }
		});
		gradientLineChart.render();

  // Mixed Chart (Barras + Líneas)
		const combinedBarLineChart = new ApexCharts(document.querySelector("#combinedBarLineChart"), {
			series: [
				{ name: "Matrículas", type: 'column', data: [] },
				{ name: "Prematrículas", type: 'line', data: [] }
				],
			chart: { height: 400, stacked: false },
			title: { text: "Gráfico Combinado (Barras + Líneas)" },
			xaxis: { categories: [] },
			stroke: { width: [0, 3] },
			fill: { type: 'solid' },
			colors: ['#FF4560', '#008FFB']
		});
		combinedBarLineChart.render();

  // Actualización de datos
		const periodSelector = document.getElementById('periodSelector');
		periodSelector.addEventListener('change', () => {
			const period = periodSelector.value;

			fetch(`data/test.php?period=${period}`)
			.then(response => response.json())
			.then(data => {
				if (!data.error) {
					const dates = data.diarias.map(entry => entry.date);
					const matriculas = data.diarias.map(entry => entry.matriculas);
					const prematriculas = data.diarias.map(entry => entry.prematriculas);

					donutChartGradient.updateSeries([matriculas.reduce((a, b) => a + b, 0), prematriculas.reduce((a, b) => a + b, 0)]);

					horizontalBarChart.updateSeries([{ data: matriculas }]);
					horizontalBarChart.updateOptions({ xaxis: { categories: dates } });

					verticalBarChartGradient.updateSeries([
						{ name: "Matrículas", data: matriculas },
						{ name: "Prematrículas", data: prematriculas }
						]);
					verticalBarChartGradient.updateOptions({ xaxis: { categories: dates } });

					stackedAreaChart.updateSeries([
						{ name: "Matrículas", data: matriculas },
						{ name: "Prematrículas", data: prematriculas }
						]);
					stackedAreaChart.updateOptions({ xaxis: { categories: dates } });

					gradientLineChart.updateSeries([
						{ name: "Matrículas", data: matriculas },
						{ name: "Prematrículas", data: prematriculas }
						]);
					gradientLineChart.updateOptions({ xaxis: { categories: dates } });

					combinedBarLineChart.updateSeries([
						{ name: "Matrículas", data: matriculas },
						{ name: "Prematrículas", data: prematriculas }
						]);
					combinedBarLineChart.updateOptions({ xaxis: { categories: dates } });
				}
			});
		});

  // Trigger initial load
		periodSelector.dispatchEvent(new Event('change'));
	});

</script>
