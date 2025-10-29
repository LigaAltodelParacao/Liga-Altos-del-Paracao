// JavaScript principal para el sistema de fútbol

// Configuración global
const FutbolManager = {
    config: {
        autoRefresh: 30000, // 30 segundos
        notificationDuration: 5000, // 5 segundos
    },
    
    // Inicialización
    init() {
        this.setupEventListeners();
        this.initTooltips();
        this.checkLiveMatches();
    },

    // Event Listeners
    setupEventListeners() {
        // Botones de acción rápida
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action]')) {
                this.handleAction(e.target.dataset.action, e.target);
            }
        });

        // Forms con confirmación
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!confirm(form.dataset.confirm)) {
                    e.preventDefault();
                }
            });
        });

        // Auto-save para formularios
        document.querySelectorAll('[data-autosave]').forEach(input => {
            input.addEventListener('change', () => {
                this.autoSave(input);
            });
        });
    },

    // Manejo de acciones
    handleAction(action, element) {
        switch (action) {
            case 'refresh-stats':
                this.refreshStats();
                break;
            case 'toggle-live':
                this.toggleLiveUpdates();
                break;
            case 'export-table':
                this.exportTable(element.dataset.format, element.dataset.categoria);
                break;
            case 'quick-search':
                this.quickSearch(element.value);
                break;
        }
    },

    // Tooltips
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    // Verificar partidos en vivo
    checkLiveMatches() {
        fetch('api/live-matches.php')
            .then(response => response.json())
            .then(data => {
                if (data.live_matches > 0) {
                    this.showLiveIndicator(data.live_matches);
                    this.startLiveUpdates();
                }
            })
            .catch(error => console.log('Error checking live matches:', error));
    },

    // Mostrar indicador de partidos en vivo
    showLiveIndicator(count) {
        const indicator = document.createElement('div');
        indicator.className = 'live-matches-indicator';
        indicator.innerHTML = `
            <i class="fas fa-broadcast-tower"></i>
            <span>${count} partido${count > 1 ? 's' : ''} en vivo</span>
        `;
        indicator.onclick = () => window.location.href = 'admin/eventos.php';
        
        document.body.appendChild(indicator);
    },

    // Iniciar actualizaciones en vivo
    startLiveUpdates() {
        if (!this.liveUpdateInterval) {
            this.liveUpdateInterval = setInterval(() => {
                this.updateLiveScores();
            }, this.config.autoRefresh);
        }
    },

    // Detener actualizaciones en vivo
    stopLiveUpdates() {
        if (this.liveUpdateInterval) {
            clearInterval(this.liveUpdateInterval);
            this.liveUpdateInterval = null;
        }
    },

    // Actualizar marcadores en vivo
    updateLiveScores() {
        fetch('api/live-scores.php')
            .then(response => response.json())
            .then(data => {
                data.matches.forEach(match => {
                    this.updateMatchScore(match);
                });
            })
            .catch(error => console.log('Error updating live scores:', error));
    },

    // Actualizar marcador individual
    updateMatchScore(match) {
        const matchElement = document.querySelector(`[data-match-id="${match.id}"]`);
        if (matchElement) {
            const scoreElement = matchElement.querySelector('.score');
            if (scoreElement) {
                scoreElement.innerHTML = `${match.goles_local} - ${match.goles_visitante}`;
            }
            
            const minuteElement = matchElement.querySelector('.minute');
            if (minuteElement) {
                minuteElement.textContent = `${match.minuto_actual}'`;
            }
            
            // Efecto visual para nuevos goles
            if (match.new_goal) {
                matchElement.classList.add('new-goal-animation');
                setTimeout(() => {
                    matchElement.classList.remove('new-goal-animation');
                }, 2000);
            }
        }
    },

    // Refrescar estadísticas
    refreshStats() {
        fetch('api/stats.php')
            .then(response => response.json())
            .then(data => {
                // Actualizar contadores
                Object.keys(data).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        this.animateNumber(element, parseInt(element.textContent), data[key]);
                    }
                });
            })
            .catch(error => console.log('Error refreshing stats:', error));
    },

    // Animar números
    animateNumber(element, start, end) {
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    },

    // Auto-guardar
    autoSave(element) {
        const form = element.closest('form');
        if (form && form.dataset.autosave) {
            const formData = new FormData(form);
            formData.append('auto_save', '1');
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification('Guardado automáticamente', 'success');
                }
            })
            .catch(error => {
                this.showNotification('Error al guardar', 'error');
            });
        }
    },

    // Búsqueda rápida
    quickSearch(query) {
        const searchResults = document.getElementById('searchResults');
        if (!searchResults) return;
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        fetch(`api/search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                
                ['equipos', 'jugadores', 'partidos'].forEach(tipo => {
                    if (data[tipo] && data[tipo].length > 0) {
                        html += `<div class="search-group">
                            <h6>${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</h6>`;
                        
                        data[tipo].forEach(item => {
                            html += `<a href="${item.url}" class="search-item">
                                <i class="${item.icon}"></i> ${item.name}
                            </a>`;
                        });
                        
                        html += '</div>';
                    }
                });
                
                if (html) {
                    searchResults.innerHTML = html;
                    searchResults.style.display = 'block';
                } else {
                    searchResults.innerHTML = '<p class="text-muted p-2">No se encontraron resultados</p>';
                    searchResults.style.display = 'block';
                }
            })
            .catch(error => {
                console.log('Error in search:', error);
            });
    },

    // Exportar tabla
    exportTable(format, categoriaId) {
        const url = `export/tabla_${format}.php?categoria=${categoriaId}`;
        if (format === 'pdf') {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    },

    // Notificaciones
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type} show`;
        notification.innerHTML = `
            <i class="fas fa-${this.getNotificationIcon(type)}"></i>
            ${message}
            <button type="button" class="btn-close btn-close-white ms-2" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, this.config.notificationDuration);
    },

    // Icono para notificaciones
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || icons.info;
    },

    // Validación de formularios
    validateForm(form) {
        let isValid = true;
        const errors = [];
        
        // Validar campos requeridos
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                errors.push(`El campo ${field.dataset.label || field.name} es requerido`);
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validar emails
        form.querySelectorAll('input[type="email"]').forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                isValid = false;
                errors.push('El email no tiene un formato válido');
                field.classList.add('is-invalid');
            }
        });
        
        // Validar fechas
        form.querySelectorAll('input[type="date"]').forEach(field => {
            if (field.value && field.min && field.value < field.min) {
                isValid = false;
                errors.push(`La fecha debe ser posterior a ${field.min}`);
                field.classList.add('is-invalid');
            }
        });
        
        return { isValid, errors };
    },

    // Validar email
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Cargar imagen preview
    previewImage(input, previewElement) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewElement.src = e.target.result;
                previewElement.style.display = 'block';
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    },

    // Drag & Drop para archivos
    setupFileUpload(element) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            element.addEventListener(eventName, this.preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            element.addEventListener(eventName, () => element.classList.add('dragover'), false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            element.addEventListener(eventName, () => element.classList.remove('dragover'), false);
        });
        
        element.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            this.handleFiles(files, element);
        }, false);
    },

    // Prevenir comportamiento por defecto
    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    },

    // Manejar archivos
    handleFiles(files, element) {
        Array.from(files).forEach(file => {
            if (this.isValidFile(file, element.dataset.accept)) {
                this.uploadFile(file, element);
            } else {
                this.showNotification('Tipo de archivo no válido', 'error');
            }
        });
    },

    // Validar archivo
    isValidFile(file, acceptTypes) {
        if (!acceptTypes) return true;
        
        const types = acceptTypes.split(',').map(type => type.trim());
        return types.some(type => {
            if (type.startsWith('.')) {
                return file.name.toLowerCase().endsWith(type.toLowerCase());
            } else {
                return file.type.includes(type);
            }
        });
    },

    // Subir archivo
    uploadFile(file, element) {
        const formData = new FormData();
        formData.append('file', file);
        
        const progressBar = this.createProgressBar();
        element.appendChild(progressBar);
        
        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Archivo subido correctamente', 'success');
                if (element.dataset.callback) {
                    window[element.dataset.callback](data);
                }
            } else {
                this.showNotification(data.error || 'Error al subir archivo', 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error al subir archivo', 'error');
        })
        .finally(() => {
            progressBar.remove();
        });
    },

    // Crear barra de progreso
    createProgressBar() {
        const progress = document.createElement('div');
        progress.className = 'upload-progress';
        progress.innerHTML = `
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 100%"></div>
            </div>
        `;
        return progress;
    },

    // Utilidades para fechas
    formatDate(date, format = 'dd/mm/yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year);
    },

    // Calcular edad
    calculateAge(birthdate) {
        const today = new Date();
        const birth = new Date(birthdate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age;
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    FutbolManager.init();
});

// Exportar para uso global
window.FutbolManager = FutbolManager;