<!-- Footer Start -->
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                Copyright &copy;
                <b>
                    <a style="color: blue;" href="javascript:void(0);">
                        <?php echo $config['web']['meta']['author'] ?>
                    </a>
                </b>
                <?php echo date( "Y"); ?>
            </div>
            <div class="col-md-6">
                <div class="text-md-right">
                    <?php echo micro_time(); ?>
                </div>
                <!--<div class="text-md-right footer-links d-none d-sm-block">
<a href="javascript:void(0);">About Us</a>
<a href="javascript:void(0);">Help</a>
<a href="javascript:void(0);">Contact Us</a>
</div>-->
            </div>
        </div>
    </div>
</footer>
<!-- End Footer -->

<!-- Right bar overlay-->
<div class="rightbar-overlay"></div>
<!-- App js -->
<script src="<?php echo $config['web']['base_url'] ?>assets/js/app.min.js"></script>

<script src="<?php echo $config['web']['base_url'] ?>assets/js/clipboard.js"></script>

<script src="<?php echo $config['web']['base_url'] ?>assets/libs/select2/js/select2.min.js"></script>

<!-- Init js -->
<script src="<?php echo $config['web']['base_url'] ?>assets/js/pages/form-advanced.init.js"></script>
</body>

</html>