    </div>  <!-- Tancament del contingut principal -->
  </main>  <!-- Fi del main -->
</div>  <!-- Fi del contenidor general -->

<!-- ===== Scripts globals de l'aplicació ===== -->
<script src="assets/js/app.js"></script>           <!-- JavaScript principal de l'app -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
      const menuBtn = document.getElementById("menuToggle");
      const sidebar = document.querySelector(".sidebar");
      
      if(menuBtn && sidebar) {
          menuBtn.addEventListener("click", function(e) {
              e.stopPropagation();
              sidebar.classList.toggle("open");
          });

          document.addEventListener("click", function(event) {
              if (sidebar.classList.contains("open") && !sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                  sidebar.classList.remove("open");
              }
          });
      }
  });
</script>
</body>
</html>
