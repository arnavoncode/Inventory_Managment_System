        </div><!-- end content -->
    </div><!-- end main -->

    <script>
        // Update time in topbar
        function updateTime() {
            const el = document.getElementById('current-time');
            if (el) {
                el.textContent = new Date().toLocaleString('en-IN', {
                    weekday:'short', day:'numeric', month:'short',
                    hour:'2-digit', minute:'2-digit'
                });
            }
        }
        updateTime();
        setInterval(updateTime, 60000);

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.style.transition = 'opacity 0.5s';
                a.style.opacity = '0';
                setTimeout(() => a.remove(), 500);
            });
        }, 3500);
    </script>
</body>
</html>
