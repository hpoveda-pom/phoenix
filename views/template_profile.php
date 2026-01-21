<?php
/*
$conn_phoenix = new mysqli('localhost', 'phoenix', 'Solid256!', 'phoenix');
if ($conn_phoenix->connect_error) {
    die("Conexión fallida: " . $conn_phoenix->connect_error);
}

$UsersId = null;
if (isset($_SESSION['UsersId'])) {
  $UsersId = $_SESSION['UsersId'];
}

if ($UsersId) {
  $query_users_info = "SELECT a.* FROM users a WHERE a.UsersId = ".$UsersId;
  if ($results_users_info = $conn_phoenix->query($query_users_info)) {
    if ($results_users_info->num_rows > 0) {
      $row_users_info = $results_users_info->fetch_assoc();
    } 
  } 
}
*/

$UsersId = null;
if (isset($_SESSION['UsersId'])) {
  $UsersId = $_SESSION['UsersId'];
}

$query_users_info = "SELECT a.* FROM users a WHERE a.UsersId = ".$UsersId;
$array_users_info = class_Recordset(1, $query_users_info, null, null, 1);
if (isset($array_users_info['data'][0])) {
  $row_users_info = $array_users_info['data'][0];
} else {
  $row_users_info = null;
}

?>

<li class="nav-item dropdown"><a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="modules/echarts/line-charts.html#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                <div class="avatar avatar-l ">
                  <img class="rounded-circle " src="assets/images/avatar.webp" alt="">
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser">
                <div class="card position-relative border-0">
                  <div class="card-body p-0">
                    <div class="text-center pt-4 pb-3">
                      <div class="avatar avatar-xl ">
                        <img class="rounded-circle " src="assets/images/avatar.webp" alt="">
                      </div>
                      <h6 class="mt-2 text-body-emphasis"><?php echo isset($row_users_info['FullName']) ? $row_users_info['FullName'] : 'Usuario'; ?></h6>
                    </div>
                    <!--
                    <div class="mb-3 mx-3"><input class="form-control form-control-sm" id="statusUpdateInput" type="text" placeholder="Update your status"></div>
                    -->
                  </div>

                  <!--
                  <div class="overflow-auto scrollbar" style="height: 10rem;">
                    <ul class="nav d-flex flex-column mb-2 pb-1">
                      
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user me-2 text-body"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span>Profile</span></a></li>
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"><svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-pie-chart me-2 text-body"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>Dashboard</a></li>
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock me-2 text-body"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>Posts &amp; Activity</a></li>

                      <li class="nav-item">
                        <a class="nav-link px-3" href="change_password.php"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings me-2 text-body"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                          Cambiar contraseña
                        </a>
                      </li>
                    
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle me-2 text-body"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Help Center</a></li>
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-globe me-2 text-body"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>Language</a></li>
                 
                    </ul>
                  </div>
                  -->
                  <div class="card-footer p-0 border-top border-translucent">
                    <!--
                    <ul class="nav d-flex flex-column my-3">
                      <li class="nav-item"><a class="nav-link px-3" href="modules/echarts/line-charts.html#!"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-plus me-2 text-body"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>Add another account</a></li>

                    </ul>
                  -->
                    <ul class="nav d-flex flex-column my-3">
                      <li class="nav-item">
                        <a class="nav-link px-3" href="change_password.php">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock me-2 text-body"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Cambiar contraseña</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link px-3" href="menu_config.php?type=sections">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings me-2 text-body"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                          Configuración
                        </a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link px-3" href="menu_config.php?type=sections">
                          <span class="me-2">-</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14px" height="14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder me-2 text-body"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                          Secciones
                        </a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link px-3" href="menu_config.php?type=groups">
                          <span class="me-2">-</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14px" height="14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder-plus me-2 text-body"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                          Grupos/Carpetas
                        </a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link px-3" href="menu_config.php?type=items">
                          <span class="me-2">-</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14px" height="14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text me-2 text-body"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                          Reportes
                        </a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link px-3" href="connections_config.php">
                          <span class="me-2">-</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14px" height="14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-database me-2 text-body"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                          Conexiones
                        </a>
                      </li>
                    </ul>
                    <hr>
                  
                    <div class="px-3"> <a class="btn btn-phoenix-secondary d-flex flex-center w-100" href="logout.php"> <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out me-2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>Desconectar</a></div>
                  <!--
                    <div class="my-2 text-center fw-bold fs-10 text-body-quaternary"><a class="text-body-quaternary me-1" href="modules/echarts/line-charts.html#!">Privacy policy</a>•<a class="text-body-quaternary mx-1" href="modules/echarts/line-charts.html#!">Terms</a>•<a class="text-body-quaternary ms-1" href="modules/echarts/line-charts.html#!">Cookies</a></div>
                  -->
                  </div>
                </div>
              </div>
            </li>