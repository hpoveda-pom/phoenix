      <nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault" style="">
        <div class="collapse navbar-collapse justify-content-between">
          <div class="navbar-logo">
            <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
            <a class="navbar-brand me-1 me-sm-3" href="index.php">
              <div class="d-flex align-items-center">
                <div class="d-flex align-items-center"><img src="assets/images/<?php echo $row_config['site_logo']; ?>" alt="<?php echo $row_config['site_name']; ?>" width="30">
                  <p class="logo-text ms-2 d-none d-sm-block"><?php echo $row_config['site_name']; ?></p>
                </div>
              </div>
            </a>
          </div>
          <?php if(1){
            require_once('views/template_searchbox.php');
          } ?>
        
          <ul class="navbar-nav navbar-nav-icons flex-row">
            <li class="nav-item">
              <div class="theme-control-toggle fa-icon-wait px-2"><input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle"><label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Switch theme" data-bs-original-title="Switch theme"><svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-moon icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg></label><label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Switch theme" data-bs-original-title="Switch theme"><svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-sun icon"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg></label></div>
            </li>
            <?php 

            //alert notify header dropdown
            if(0){
              require_once('views/template_notify.php');
            }

            //apps menu header
            if(0){
              require_once('views/template_apps.php');
            }

            //profile header
            if (1) {
              require_once('views/template_profile.php');
            }
            ?>
          </ul>
        </div>
      </nav>
      <script src="assets/js/navbar.js"></script>