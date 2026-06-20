</div> </div> </main>
    
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">V 1.0</div>
        <strong><?= $settings['footer_text'] ?></strong>
    </footer>
</div> <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>
<script>
    // ----------------------------------------------------
    // Issue Distribution Chart (Doughnut)
    // ----------------------------------------------------
    const catLabels = <?= json_encode(array_column($categoryStats ?? [], 'category_name')) ?>;
    const catData = <?= json_encode(array_column($categoryStats ?? [], 'count')) ?>;
    
    if (document.getElementById('categoryHeatmap')) {
        new Chart(document.getElementById('categoryHeatmap'), {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: [
                        '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF'
                    ],
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
                },
                cutout: '65%'
            }
        });
    }

    // Theme Toggle Logic with Persistence
    // ... rest of toggle code ...
    const toggleButton = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const htmlElement = document.documentElement;

    // 1. Function to update Icon
    function updateIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill');
        } else {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
        }
    }

    // 2. Initialize Icon on Load
    const currentTheme = localStorage.getItem('theme') || 'light';
    updateIcon(currentTheme);

    // 3. Handle Click
    toggleButton.addEventListener('click', () => {
        const current = htmlElement.getAttribute('data-bs-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        
        // Apply
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme); // SAVE IT
        updateIcon(newTheme);
    });
</script>
</body>
</html>