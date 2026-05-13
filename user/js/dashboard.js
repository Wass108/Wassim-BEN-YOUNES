        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }

        document.addEventListener('wheel', function(e) {
            if (e.shiftKey || Math.abs(e.deltaX) > 0) {
                e.preventDefault();
            }
        }, { passive: false });

        window.addEventListener('scroll', function() {
            if (window.scrollX !== 0) {
                window.scrollTo(0, window.scrollY);
            }
        });

        function preventHorizontalScroll() {
            const containers = document.querySelectorAll('.profile-card, .stat-card, .orders-card, .overflow-hidden, .overflow-x-auto, div[class*="overflow"]');
            
            containers.forEach(container => {
                container.addEventListener('wheel', function(e) {
                    if (Math.abs(e.deltaX) > 0) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, { passive: false });

                container.addEventListener('scroll', function() {
                    if (this.scrollLeft !== 0) {
                        this.scrollLeft = 0;
                    }
                });
            });
        }

        preventHorizontalScroll();

        function loadCommandes(page) {
            const commandesContainer = document.getElementById('commandes-container');
            
            commandesContainer.style.opacity = '0.5';
            
            fetch(`get_commandes.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    commandesContainer.innerHTML = data.html;
                    commandesContainer.style.opacity = '1';
                    
                    const url = new URL(window.location);
                    url.searchParams.set('page', page);
                    window.history.pushState({}, '', url);
                    
                    preventHorizontalScroll();
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des commandes:', error);
                    commandesContainer.style.opacity = '1';
                });
        }
        
        window.loadCommandes = loadCommandes;