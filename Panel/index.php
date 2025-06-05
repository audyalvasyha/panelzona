<?php
require 'mainconfig.php';
if (!isset($_SESSION)) {
    session_start();
}
// anti flood protection
if($_SESSION['last_session_request'] > time() - 2){
    // users will be redirected to this page if it makes requests faster than 2 seconds
    header("location: http://www.google.com/");//awokawok
    exit;
}
$_SESSION['last_session_request'] = time();
if (!isset($_SESSION['login'])) {
exit(header("Location: ".$config['web']['base_url']."auth/login.php"));
}
require 'lib/header.php';
?>
<div class="row">
    <div class="col-lg-12 text-center" style="margin: 15px 0;">
        <h3 class="text-uppercase"><i class="fa fa-user-circle-o fa-fw"></i> Informasi Akun</h3>
    </div>
    <div class="col-lg-8">
        <div class="card-box">
            <h4 class="m-t-0 m-b-30 header-title"><i class="fa fa-area-chart"></i> Grafik Pesanan & Deposit 7 Hari Terakhir</h4>
            <div id="last-order-chart" style="height: 200px;"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="col-12">
            <div class="widget-rounded-circle card-box bg-pattern">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar-lg rounded-circle bg-success">
                            <i class="fe-shopping-cart font-24 avatar-title"> </i>
                        </div>
                    </div>
                    <div class="col">
                        <p class="card-category">
                            Total Pesanan
                        </p>
                        <h5 class="card-title">
                            Rp
                            <?php echo number_format($model->db_query($db, "SUM(price) as total", "orders WHERE user_id = '".$_SESSION['login']."'")['rows']['total'],0,',','.') ?> (<?php echo number_format($model->db_query($db, "*", "orders
                            WHERE user_id = '".$_SESSION['login']."'")['count'],0,',','.') ?>)
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="widget-rounded-circle card-box bg-pattern">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar-lg rounded-circle bg-warning">
                            <i class="fe-credit-card font-24 avatar-title"> </i>
                        </div>
                    </div>
                    <div class="col">
                        <p class="card-category">
                            Total Deposit
                        </p>
                        <h5 class="card-title">
                            $
                            <?php echo number_format($model->db_query($db, "SUM(amount) as total", "deposits WHERE user_id = '".$_SESSION['login']."'")['rows']['total'],0,',','.') ?> (<?php echo number_format($model->db_query($db, "*",
                            "deposits WHERE user_id = '".$_SESSION['login']."'")['count'],0,',','.') ?>)
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="widget-rounded-circle card-box bg-pattern">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar-lg rounded-circle bg-info">
                            <i class="fe-user font-24 avatar-title"> </i>
                        </div>
                    </div>
                    <div class="col">
                        <p class="card-category">
                            Saldo
                        </p>
                        <h5 class="card-title">
                            Rp
                            <?php echo number_format($login['balance'],0,',','.'); ?>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 text-center" style="margin: 15px 0;">
        <h3 class="text-uppercase"><i class="fa fa-bullhorn fa-fw"></i> Informasi Webiste</h3>
    </div>
    <div class="col-12">
        <div class="card-box">
            <h4 class="m-t-0 m-b-30 header-title"><i class="fa fa-info-circle"></i> 5 Informasi Terbaru</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 250px;">TANGGAL/WAKTU</th>
                            <th>KONTEN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$news = $model->db_query($db, "*", "news", null, "id DESC", "LIMIT 5"); if ($news['count'] == 1) { ?>
                        <tr>
                            <td><?php echo format_date(substr($news['rows']['created_at'], 0, -9)).", ".substr($news['rows']['created_at'], -8) ?></td>
                            <td><?php echo nl2br($news['rows']['content']) ?></td>
                        </tr>
                        <?php
} else {
	foreach ($news['rows'] as $key =>
                        $value) { ?>
                        <tr>
                            <td><?php echo format_date(substr($value['created_at'], 0, -9)).", ".substr($value['created_at'], -8) ?></td>
                            <td><?php echo nl2br($value['content']) ?></td>
                        </tr>
                        <?php
	}
}
if ($news['count'] >= 5) { ?>
                        <tr>
                            <td colspan="3" align="center">
                                <a href="<?php echo $config['web']['base_url'] ?>news">Lihat semua...</a>
                            </td>
                        </tr>

                        <?php
}
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
const areaChartEl = document.querySelector('#lineAreaChart'),
  areaChartConfig = {
    chart: {
      height: 400,
      type: 'area',
      parentHeightOffset: 0,
      toolbar: {
        show: false
      }
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      show: false,
      curve: 'straight'
    },
    legend: {
      show: true,
      position: 'top',
      horizontalAlign: 'start',
      labels: {
        colors: legendColor,
        useSeriesColors: false
      }
    },
    grid: {
      borderColor: borderColor,
      xaxis: {
        lines: {
          show: true
        }
      }
    },
    colors: [chartColors.area.series3, chartColors.area.series2, chartColors.area.series1],
    series: [
      {
        name: 'Visits',
        data: [100, 120, 90, 170, 130, 160, 140, 240, 220, 180, 270, 280, 375]
      },
      {
        name: 'Clicks',
        data: [60, 80, 70, 110, 80, 100, 90, 180, 160, 140, 200, 220, 275]
      },
      {
        name: 'Sales',
        data: [20, 40, 30, 70, 40, 60, 50, 140, 120, 100, 140, 180, 220]
      }
    ],
    xaxis: {
      categories: [
        '7/12',
        '8/12',
        '9/12',
        '10/12',
        '11/12',
        '12/12',
        '13/12',
        '14/12',
        '15/12',
        '16/12',
        '17/12',
        '18/12',
        '19/12',
        '20/12'
      ],
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '13px'
        }
      }
    },
    yaxis: {
      labels: {
        style: {
          colors: labelColor,
          fontSize: '13px'
        }
      }
    },
    fill: {
      opacity: 1,
      type: 'solid'
    },
    tooltip: {
      shared: false
    }
  };
if (typeof areaChartEl !== undefined && areaChartEl !== null) {
  const areaChart = new ApexCharts(areaChartEl, areaChartConfig);
  areaChart.render();
}
</script>
<?php
require 'lib/footer.php';
?>