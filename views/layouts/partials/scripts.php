      <!-- âœ… Vue production local asset -->
      <script type="text/javascript" src="/<?= PROJECT_DIR ?>/public/assets/js/vue.global.prod.js"></script>

      <!-- âœ… Lucide production local asset -->
      <script type="text/javascript" src="/<?= PROJECT_DIR ?>/public/assets/js/lucide.min.js"></script>

      <!-- âœ… Inject PHP data -->
      <script>
        window.__APP__ = {
          role: <?= json_encode($role) ?>,
          menuItems: <?= json_encode($menuItems) ?>,
          currentPath: <?= json_encode($currentPath) ?>,
          basePath: <?= json_encode($basePath) ?>,
          userDisplayName: <?= json_encode($userDisplayName) ?>,
          userEmail: <?= json_encode($userEmail) ?>,
          userInitials: <?= json_encode($initials) ?>,
          userAvatar: <?= json_encode($userAvatar) ?>,
          userSignature: <?= json_encode($userSignature) ?>,
          userProfessionalTitle: <?= json_encode($userProfessionalTitle) ?>,
          userFullNameReport: <?= json_encode($userFullNameReport) ?>,
          userFirstName: <?= json_encode($userFirstName) ?>,
          userLastName: <?= json_encode($userLastName) ?>,
          userBirthdate: <?= json_encode($userBirthdate) ?>,
          userSex: <?= json_encode($userSex) ?>,
          userContactNumber: <?= json_encode($userContactNumber) ?>
        };
      </script>




      <!-- âœ… Vue App -->
      <script>
        window.addEventListener('error', function (e) {
          alert("JS Error: " + e.message + " in " + e.filename + " line " + e.lineno);
        });
        const { createApp, nextTick } = Vue;

        const app = createApp({
          data() {
            return {
              isOpen: localStorage.getItem('citilife_sidebar_open') !== 'false',
              isMobile: window.innerWidth < 768,
              mobileMenuOpen: false,
              mobileProfileMenuOpen: false,
              profileMenuOpen: false,
              notificationMenuOpen: false,
              notificationCount: 0,
              notifications: [],
              menuItems: window.__APP__.menuItems,
              currentPath: window.__APP__.currentPath,
              basePath: window.__APP__.basePath,
              // New Profile Data
              userDisplayName: window.__APP__.userDisplayName,
              userEmail: window.__APP__.userEmail,
              userInitials: window.__APP__.userInitials,
              userAvatar: window.__APP__.userAvatar,
              settingsModalOpen: false,
              settingsActiveTab: 'general',
              editDisplayName: '',
              editEmail: '',
              emailChangeState: 'idle',
              otpCode: '',
              uploadFile: null,
              uploadPreview: null,
              savingProfile: false,
              isRequestingReset: false,
              themeMode: localStorage.getItem('citilife_theme') || 'system',
              // RadTech Settings
              userSignature: window.__APP__.userSignature,
              userProfessionalTitle: window.__APP__.userProfessionalTitle,
              userFullNameReport: window.__APP__.userFullNameReport,
              editFullName: window.__APP__.userFullNameReport || window.__APP__.userDisplayName,
              editProfessionalTitle: window.__APP__.userProfessionalTitle || '',
              signatureFile: null,
              signaturePreview: null,
              savingRadtechSettings: false,
              role: window.__APP__.role,
              // General Settings — Notification Toggles
              notifEmail: localStorage.getItem('citilife_notif_email') !== 'false',
              notifSystem: localStorage.getItem('citilife_notif_system') !== 'false',
              notifSound: localStorage.getItem('citilife_notif_sound') === 'true',
              toasts: [],
              editFirstName: '',
              editLastName: '',
              editBirthdate: '',
              editSex: 'Male',
              editContactNumber: '',
              editPassword: '',
              editConfirmPassword: '',
              showNewPassword: false,
              showConfirmPassword: false,
              savingPassword: false,
              themeDropdownOpen: false,
            };
          },
          computed: {
            pwHasMinLength() {
              return this.editPassword.length >= 8;
            },
            pwHasUppercase() {
              return /[A-Z]/.test(this.editPassword);
            },
            pwHasNumber() {
              return /[0-9]/.test(this.editPassword);
            },
            pwHasSpecial() {
              return /[^A-Za-z0-9]/.test(this.editPassword);
            },
            pwPassedCount() {
              let count = 0;
              if (this.pwHasMinLength) count++;
              if (this.pwHasUppercase) count++;
              if (this.pwHasNumber) count++;
              if (this.pwHasSpecial) count++;
              return count;
            },
            strengthPercent() {
              if (!this.editPassword) return 0;
              return (this.pwPassedCount / 4) * 100;
            },
            strengthLabel() {
              if (!this.editPassword) return '';
              if (this.pwPassedCount <= 1) return 'Weak';
              if (this.pwPassedCount <= 3) return 'Medium';
              return 'Strong';
            },
            strengthColor() {
              if (!this.editPassword) return 'transparent';
              if (this.pwPassedCount <= 1) return '#ef4444';
              if (this.pwPassedCount <= 3) return '#eab308';
              return '#10b981';
            },
            passwordsMatch() {
              if (!this.editConfirmPassword) return false;
              return this.editPassword === this.editConfirmPassword;
            }
          },
          mounted() {
            // ===== DISMISS LOADING SKELETON =====
            const loader = document.getElementById('app-loading');
            if (loader) {
              loader.classList.add('fade-out');
              setTimeout(() => {
                loader.classList.add('hidden');
              }, 280);
            }
            // =====================================

            // Load theme from localStorage — support system/dark/light
            this.themeMode = localStorage.getItem('citilife_theme') || 'system';
            console.log("Vue Mounted - themeMode from localStorage:", this.themeMode);
            this._applyTheme(this.themeMode);

            // Watch OS preference changes live (affects 'system' mode)
            if (window.matchMedia) {
              window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.themeMode === 'system') {
                  this._applyThemeDark(e.matches);
                }
              });
            }
            // Detect window resize for mobile
            window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 768; });

            this.fetchNotifications(true);
            setInterval(() => this.fetchNotifications(false), 5000); // 5s fetch interval

            // Close profile menu and notifications when clicking outside
            document.addEventListener("mousedown", (e) => {
              if (this.$refs.profileMenuRef && !this.$refs.profileMenuRef.contains(e.target)) {
                this.profileMenuOpen = false;
              }
              if (this.$refs.mobileProfileMenuRef && !this.$refs.mobileProfileMenuRef.contains(e.target)) {
                this.mobileProfileMenuOpen = false;
              }
              if (this.notificationMenuOpen) {
                const inDesktopNotif = this.$refs.notificationMenuRef && this.$refs.notificationMenuRef.contains(e.target);
                const inMobileNotif = this.$refs.mobileNotificationMenuRef && this.$refs.mobileNotificationMenuRef.contains(e.target);
                const isNotifButton = e.target.closest('[aria-label="Notifications"]') || e.target.closest('button[onclick*="toggleNotificationMenu"]');

                if (!inDesktopNotif && !inMobileNotif && !isNotifButton) {
                  this.notificationMenuOpen = false;
                }
              }
              if (this.themeDropdownOpen && this.$refs.themeDropdownRef && !this.$refs.themeDropdownRef.contains(e.target)) {
                this.themeDropdownOpen = false;
              }
            });

            // Close on Escape key
            document.addEventListener("keydown", (e) => {
              if (e.key === "Escape") {
                this.profileMenuOpen = false;
                this.mobileProfileMenuOpen = false;
                this.notificationMenuOpen = false;
                this.settingsModalOpen = false;
              }
            });

            window.showSuccess = (msg) => {
              this.showToast('Success', msg, 'success');
            };
            window.showError = (msg) => {
              this.showToast('Error', msg, 'error');
            };

            nextTick(() => this.renderIcons());
          },
          methods: {
            requestPasswordReset() {
              if (this.isRequestingReset) return;
              if (!this.userEmail) {
                this.showToast('Error', 'No email associated with your account.', 'error');
                return;
              }
              
              this.isRequestingReset = true;
              const formData = new FormData();
              formData.append('email', this.userEmail);
              
              fetch('/<?= PROJECT_DIR ?>/app/api/request_password_reset.php', {
                method: 'POST',
                body: formData
              })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  this.showToast('Success', 'A password reset link has been sent to your email.', 'success');
                } else {
                  this.showToast('Error', data.error || 'Failed to send password reset email.', 'error');
                }
              })
              .catch(err => {
                console.error(err);
                this.showToast('Error', 'Failed to connect. Please check your connection.', 'error');
              })
              .finally(() => {
                this.isRequestingReset = false;
              });
            },
            openSettings(tab = 'general') {
              this.profileMenuOpen = false;
              this.mobileProfileMenuOpen = false;
              this.mobileMenuOpen = false;
              this.settingsActiveTab = tab;

              this.editDisplayName = this.userDisplayName;
              this.editEmail = this.userEmail;
              this.emailChangeState = 'idle';
              this.otpCode = '';
              this.uploadFile = null;
              this.uploadPreview = null;

              this.editFullName = this.userFullNameReport || this.userDisplayName;
              this.editProfessionalTitle = this.userProfessionalTitle || '';
              this.signatureFile = null;
              this.signaturePreview = null;

              if (this.role === 'patient') {
                this.editFirstName = window.__APP__.userFirstName || '';
                this.editLastName = window.__APP__.userLastName || '';
                this.editBirthdate = window.__APP__.userBirthdate || '';
                this.editSex = window.__APP__.userSex || 'Male';
                this.editContactNumber = window.__APP__.userContactNumber || '';
              }
              this.editPassword = '';
              this.editConfirmPassword = '';
              this.showNewPassword = false;
              this.showConfirmPassword = false;

              this.settingsModalOpen = true;
              nextTick(() => this.renderIcons());
            },
            selectSettingsTab(tab) {
              this.settingsActiveTab = tab;
              nextTick(() => this.renderIcons());
            },
            openEditProfileModal() {
              this.openSettings('profile');
            },
            openPersonalizationModal() {
              this.openSettings('appearance');
            },
            openRadtechSettingsModal() {
              this.openSettings('reports');
            },
            handleSignatureChange(e) {
              const file = e.target.files[0];
              if (file) {
                this.signatureFile = file;
                const reader = new window.FileReader();
                reader.onload = e => this.signaturePreview = e.target.result;
                reader.readAsDataURL(file);
              }
            },
            saveRadtechSettings() {
              if (!this.editFullName) {
                alert('Full Name is required.');
                return;
              }
              this.savingRadtechSettings = true;
              const formData = new window.FormData();
              formData.append('action', 'update_radtech_settings');
              formData.append('report_full_name', this.editFullName);
              formData.append('professional_title', this.editProfessionalTitle);
              if (this.signatureFile) {
                formData.append('signature', this.signatureFile);
              }

              fetch('/<?= PROJECT_DIR ?>/app/api/update_profile.php', {
                method: 'POST',
                body: formData
              })
                .then(res => res.json())
                .then(data => {
                  this.savingRadtechSettings = false;
                  if (data.success) {
                    // UPDATE ONLY REPORT-SPECIFIC STATE
                    this.userFullNameReport = data.full_name_report;
                    this.userProfessionalTitle = data.professional_title;
                    window.__APP__.userFullNameReport = data.full_name_report;
                    window.__APP__.userProfessionalTitle = data.professional_title;

                    if (data.signature) {
                      this.userSignature = data.signature;
                      window.__APP__.userSignature = data.signature;
                    }
                    this.settingsModalOpen = false;

                    if (window.showSuccess) {
                      showSuccess('Report settings updated!');
                    }
                  } else {
                    alert(data.error || 'Failed to update settings.');
                  }
                })
                .catch(err => {
                  console.error(err);
                  this.savingRadtechSettings = false;
                  alert('A network error occurred.');
                });
            },
            handleAvatarChange(e) {
              const file = e.target.files[0];
              if (file) {
                this.uploadFile = file;
                const reader = new window.FileReader();
                reader.onload = e => this.uploadPreview = e.target.result;
                reader.readAsDataURL(file);
              }
            },
            _applyThemeDark(isDark) {
              console.log("Vue _applyThemeDark called with:", isDark);
              if (isDark) {
                document.documentElement.classList.add('theme-dark', 'dark');
                document.body.classList.add('theme-dark', 'dark');
                document.documentElement.style.colorScheme = 'dark';
              } else {
                document.documentElement.classList.remove('theme-dark', 'dark');
                document.body.classList.remove('theme-dark', 'dark');
                document.documentElement.style.colorScheme = 'light';
              }
            },
            _applyTheme(mode) {
              const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
              if (mode === 'dark') {
                this._applyThemeDark(true);
              } else if (mode === 'light') {
                this._applyThemeDark(false);
              } else {
                // system
                this._applyThemeDark(prefersDark);
              }
            },
            setTheme(themeName) {
              this.themeMode = themeName;
              localStorage.setItem('citilife_theme', themeName);
              this._applyTheme(themeName);
              // Re-render icons after theme change
              nextTick(() => this.renderIcons());
            },
            requestEmailChange() {
              this.emailChangeState = 'sending';
              fetch('/<?= PROJECT_DIR ?>/app/api/send_email_change_otp.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    this.emailChangeState = 'verifying';
                    if (window.showSuccess) showSuccess('OTP sent to your email.');
                  } else {
                    this.emailChangeState = 'idle';
                    alert(data.error || 'Failed to send OTP.');
                  }
                })
                .catch(err => {
                  console.error(err);
                  this.emailChangeState = 'idle';
                  alert('Network error occurred.');
                });
            },
            verifyEmailChangeOtp() {
              if (!this.otpCode) return;
              fetch('/<?= PROJECT_DIR ?>/app/api/verify_email_change_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: this.otpCode })
              })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    this.emailChangeState = 'editable';
                    if (window.showSuccess) showSuccess('Email verified. You can now change it.');
                  } else {
                    alert(data.error || 'Invalid OTP.');
                  }
                })
                .catch(err => {
                  console.error(err);
                  alert('Network error occurred.');
                });
            },
            saveProfile() {
              if (this.role === 'patient') {
                if (!this.editFirstName || !this.editLastName || !this.editEmail || !this.editBirthdate || !this.editContactNumber) {
                  alert('Please fill out all required fields.');
                  return;
                }
              } else {
                if (!this.editDisplayName || !this.editEmail) return;
              }
              this.savingProfile = true;

              const formData = new window.FormData();
              formData.append('action', 'update_profile');
              formData.append('email', this.editEmail);

              if (this.role === 'patient') {
                formData.append('first_name', this.editFirstName);
                formData.append('last_name', this.editLastName);
                formData.append('birthdate', this.editBirthdate);
                formData.append('sex', this.editSex);
                formData.append('contact_number', this.editContactNumber);
              } else {
                formData.append('system_name', this.editDisplayName);
              }

              if (this.uploadFile) {
                formData.append('avatar', this.uploadFile);
              }

              fetch('/<?= PROJECT_DIR ?>/app/api/update_profile.php', {
                method: 'POST',
                body: formData
              })
                .then(res => res.json())
                .then(data => {
                  this.savingProfile = false;
                  if (data.success) {
                    this.userDisplayName = data.name;
                    window.__APP__.userDisplayName = data.name;
                    this.userEmail = data.email;
                    window.__APP__.userEmail = data.email;
                    this.userInitials = data.initials;
                    if (data.avatar) {
                      this.userAvatar = data.avatar;
                      window.__APP__.userAvatar = data.avatar;
                    }

                    if (this.role === 'patient') {
                      window.__APP__.userFirstName = data.first_name;
                      window.__APP__.userLastName = data.last_name;
                      window.__APP__.userBirthdate = data.birthdate;
                      window.__APP__.userSex = data.sex;
                      window.__APP__.userContactNumber = data.contact_number;

                      this.editFirstName = data.first_name;
                      this.editLastName = data.last_name;
                      this.editBirthdate = data.birthdate;
                      this.editSex = data.sex;
                      this.editContactNumber = data.contact_number;
                    }

                    this.settingsModalOpen = false;
                    if (window.showSuccess) {
                      showSuccess('Profile updated successfully!');
                    }
                  } else {
                    alert(data.error || 'Failed to update profile.');
                  }
                })
                .catch(err => {
                  console.error(err);
                  this.savingProfile = false;
                  alert('A network error occurred.');
                });
            },
            savePassword() {
              if (!this.editPassword || !this.editConfirmPassword) {
                alert('Please enter a new password and confirm it.');
                return;
              }
              if (this.editPassword !== this.editConfirmPassword) {
                alert('Passwords do not match.');
                return;
              }
              if (!this.pwHasMinLength || !this.pwHasUppercase || !this.pwHasNumber || !this.pwHasSpecial) {
                alert('Password does not meet complexity requirements.');
                return;
              }

              this.savingPassword = true;
              const formData = new window.FormData();
              formData.append('action', 'update_profile');
              formData.append('password', this.editPassword);
              formData.append('email', this.userEmail);

              if (this.role === 'patient') {
                formData.append('first_name', window.__APP__.userFirstName || '');
                formData.append('last_name', window.__APP__.userLastName || '');
                formData.append('birthdate', window.__APP__.userBirthdate || '');
                formData.append('sex', window.__APP__.userSex || 'Male');
                formData.append('contact_number', window.__APP__.userContactNumber || '');
              } else {
                formData.append('system_name', this.userDisplayName);
              }

              fetch('/<?= PROJECT_DIR ?>/app/api/update_profile.php', {
                method: 'POST',
                body: formData
              })
                .then(res => res.json())
                .then(data => {
                  this.savingPassword = false;
                  if (data.success) {
                    this.editPassword = '';
                    this.editConfirmPassword = '';
                    this.settingsModalOpen = false;
                    if (window.showSuccess) {
                      showSuccess('Password updated successfully!');
                    }
                  } else {
                    alert(data.error || 'Failed to update password.');
                  }
                })
                .catch(err => {
                  console.error(err);
                  this.savingPassword = false;
                  alert('A network error occurred.');
                });
            },
            toggleSidebar() {
              this.isOpen = !this.isOpen;
              localStorage.setItem('citilife_sidebar_open', this.isOpen);

              // Sync bootstrap class for skeleton/layout consistency
              if (this.isOpen) {
                document.documentElement.classList.remove('sidebar-collapsed');
              } else {
                document.documentElement.classList.add('sidebar-collapsed');
              }
            },
            toggleMobileMenu() {
              this.mobileMenuOpen = !this.mobileMenuOpen;
            },
            toggleNotificationMenu() {
              this.notificationMenuOpen = !this.notificationMenuOpen;
            },
            closeNotificationMenu() {
              this.notificationMenuOpen = false;
            },
            fetchNotifications(isInitial = false) {
              fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php')
                .then(res => res.json())
                .then(data => {
                  if (!data.error) {
                    const oldIds = this.notifications.map(n => n.id);
                    this.notificationCount = data.unread_count;
                    this.notifications = data.notifications;

                    // Play sound and display toast alerts for new unread notifications
                    if (!isInitial && data.notifications.length > 0) {
                      const newNotifs = data.notifications.filter(n => !oldIds.includes(n.id));
                      if (newNotifs.length > 0) {
                        if (this.notifSound) {
                          this.playNotificationSound();
                        }
                        if (this.notifSystem) {
                          newNotifs.forEach(n => {
                            this.showToast(n.title, n.message, 'info', n.link, n.id);
                          });
                        }
                      }
                    }

                    nextTick(() => this.renderIcons());
                  }
                })
                .catch(err => console.error('Error fetching notifications:', err));
            },
            showToast(title, message, type = 'info', link = '#', notificationId = null) {
              const id = Date.now() + Math.random();
              this.toasts.push({ id, title, message, type, link, notificationId });
              nextTick(() => this.renderIcons());
              setTimeout(() => {
                this.dismissToast(id);
              }, 5000); // auto dismiss after 5s
            },
            getToastIcon(toast) {
              const title = (toast.title || '').toLowerCase();
              const message = (toast.message || '').toLowerCase();

              if (toast.type === 'success') return 'check-circle';
              if (toast.type === 'error') return 'alert-circle';

              // Case notifications
              if (title.includes('case') || title.includes('kaso') || message.includes('case') || message.includes('kaso') || title.includes('xray') || message.includes('xray')) {
                return 'activity'; // pulse waveform for medical cases
              }

              // Default
              return 'bell';
            },
            getToastStyle(toast) {
              const icon = this.getToastIcon(toast);
              if (toast.type === 'success') {
                return { bg: '#f0fdf4', color: '#16a34a' }; // Green
              }
              if (toast.type === 'error') {
                return { bg: '#fef2f2', color: '#dc2626' }; // Red
              }
              if (icon === 'activity') {
                return { bg: '#fff5f5', color: '#dc2626' }; // Soft red matching CitiLife brand!
              }
              return { bg: '#eff6ff', color: '#3b82f6' }; // Blue
            },
            dismissToast(id) {
              this.toasts = this.toasts.filter(t => t.id !== id);
            },
            handleToastClick(toast) {
              this.dismissToast(toast.id);
              if (toast.notificationId) {
                this.markAsRead(toast.notificationId, toast.link);
              } else if (toast.link && toast.link !== '#') {
                window.location.href = toast.link;
              }
            },
            playNotificationSound() {
              try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();

                const playTone = (freq, time, duration, volume) => {
                  const osc = ctx.createOscillator();
                  const gainNode = ctx.createGain();

                  osc.type = 'sine';
                  osc.frequency.setValueAtTime(freq, time);

                  gainNode.gain.setValueAtTime(0, time);
                  gainNode.gain.linearRampToValueAtTime(volume, time + 0.02);
                  gainNode.gain.exponentialRampToValueAtTime(0.0001, time + duration);

                  osc.connect(gainNode);
                  gainNode.connect(ctx.destination);

                  osc.start(time);
                  osc.stop(time + duration);
                };

                // Modern, bright triple chime with clear audibility
                playTone(880.00, ctx.currentTime, 0.4, 0.35); // A5 (Fundamental)
                playTone(1318.51, ctx.currentTime + 0.08, 0.6, 0.35); // E6 (Perfect Fifth)
                playTone(1760.00, ctx.currentTime + 0.12, 0.8, 0.15); // A6 (High Octave overlay for brightness)
              } catch (e) {
                console.error('Audio play error:', e);
              }
            },

            markAllRead() {
              fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read' })
              }).then(() => {
                this.notificationCount = 0;
                this.notifications = [];
                this.notificationMenuOpen = false;
              });
            },
            markAsRead(id, link) {
              fetch('/<?= PROJECT_DIR ?>/app/api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read', notification_id: id })
              }).then(() => {
                if (link && link !== '#') {
                  window.location.href = link;
                } else {
                  this.fetchNotifications();
                }
              });
            },
            isActive(href) {
              try {
                const currentUrl = new window.URL(window.location.href);
                // Extract current page: prefer ?page= query param, fallback to last path segment
                const _currentPageParam = currentUrl.searchParams.get('page');
                let _currentPathSeg = currentUrl.pathname.replace(/\/$/, '').split('/').pop();
                // Treat index.php as dashboard if no page param is set
                if (!_currentPageParam && (_currentPathSeg === 'index.php' || _currentPathSeg === 'CitiLife-System' || _currentPathSeg === '')) {
                  _currentPathSeg = 'dashboard';
                }
                const currentPage = _currentPageParam || _currentPathSeg || 'dashboard';
                // Always use the session role â€” never use the URL `role` filter param
                // (the `role` query param is a *filter*, not the user's actual role)
                const currentRole = window.__APP__.role;
                const targetRole = window.__APP__.role;

                // Try parsing the target href
                const targetUrl = new window.URL(href, window.location.origin + (window.__APP__.basePath || ""));

                // Get page from query param; if not present, extract from the last path segment
                let targetPage = targetUrl.searchParams.get('page');
                if (!targetPage) {
                  const pathSegments = targetUrl.pathname.replace(/\/$/, '').split('/');
                  targetPage = pathSegments[pathSegments.length - 1] || 'dashboard';
                }

                // Special case associations: keep sidebar item active for sub-pages
                if (targetPage === 'patient-lists' && ['patient-lists', 'patient-approval', 'patient-details'].includes(currentPage)) {
                  return true;
                }
                if (targetPage === 'xray-patient-records' && ['xray-patient-records', 'records-history'].includes(currentPage)) {
                  return true;
                }
                if (targetPage === 'record-request' && ['record-request', 'view-record-request'].includes(currentPage)) {
                  return true;
                }
                if (targetPage === 'branch-xray-cases' && ['branch-xray-cases', 'patient-details', 'records-history'].includes(currentPage)) {
                  return true;
                }

                // Radiologist specific associations
                if (currentRole === 'radiologist' || currentRole === 'radtech' || currentRole === 'admin_central' || currentRole === 'branch_admin' || currentRole === 'it_admin') {
                  if (targetPage === 'worklist' && ['worklist', 'patient-queue', 'case-review'].includes(currentPage)) {
                    return true;
                  }
                  if (targetPage === 'patient-history' && ['patient-history', 'patient-records-history'].includes(currentPage)) {
                    return true;
                  }
                }

                // Default: match by page and role
                return currentPage === targetPage && currentRole === targetRole;
              } catch (e) {
                const base = window.__APP__.basePath || "";
                const fullHref = href.startsWith(base) ? href : (base + href);
                return this.currentPath === fullHref;
              }
            },
            renderIcons() {
              if (window.lucide) lucide.createIcons();
            }
          },
          watch: {
            notifEmail(val) {
              localStorage.setItem('citilife_notif_email', val);
            },
            notifSystem(val) {
              localStorage.setItem('citilife_notif_system', val);
            },
            notifSound(val) {
              localStorage.setItem('citilife_notif_sound', val);
              if (val) {
                this.playNotificationSound();
              }
            }
          },
          updated() {
            nextTick(() => this.renderIcons());
          }
        });
        app.config.errorHandler = function (err, vm, info) {
          alert("Vue Error: " + err.toString() + " | info: " + info);
        };
        app.mount("#app");

        // Real-time date and time for topbar
        function updateTopbarDateTime() {
          const now = new window.Date();

          const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
          const optionsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };

          const dtElem = document.getElementById('topbarDateTime');
          if (!dtElem) return;

          const dateText = now.toLocaleDateString(undefined, options);
          const timeText = now.toLocaleTimeString(undefined, optionsTime);
          dtElem.textContent = `${dateText} at ${timeText}`;
        }

        updateTopbarDateTime();
        setInterval(updateTopbarDateTime, 1000);

        // AJAX Polling for Real-Time Updates
        if (document.querySelectorAll('.realtime-update').length > 0) {
          setInterval(() => {
            // Use the persistent currentPath from __APP__ instead of window.location.href 
            // to survive URL cleaning (Stealth Mode) used in Patient portal views.
            let baseUrl = window.location.origin + (window.__APP__.currentPath || window.location.pathname);
            let url = baseUrl + (baseUrl.includes('?') ? '&ajax_polling=1' : '?ajax_polling=1');

            fetch(url)
              .then(res => res.text())
              .then(html => {
                const doc = new window.DOMParser().parseFromString(html, 'text/html');
                document.querySelectorAll('.realtime-update').forEach(el => {
                  if (el.id) {
                    const newEl = doc.getElementById(el.id);
                    if (newEl) el.innerHTML = newEl.innerHTML;
                  }
                });
                // Re-initialize any lucide icons in the replaced content
                if (window.lucide) lucide.createIcons();
                // Notify pagination scripts to re-apply page filter
                document.dispatchEvent(new window.CustomEvent('realtime:updated'));
              })
              .catch(err => console.error('Polling error:', err));
          }, 3000); // 3 seconds interval
        }

        // Global Radiologist Activity Polling (survives AJAX replacements)
        function checkRadStatusGlobal() {
          const dot = document.getElementById('rad-activity-dot');
          if (!dot) return;

          const caseId = dot.getAttribute('data-case-id');
          if (!caseId) return;

          fetch(`/<?= PROJECT_DIR ?>/app/api/case_activity.php?action=status&case_id=${caseId}&_t=` + Date.now())
            .then(res => res.json())
            .then(data => {
              if (!data.success) return;
              const currentDot = document.getElementById('rad-activity-dot');
              if (!currentDot) return;

              currentDot.classList.remove('bg-gray-400', 'bg-green-500', 'bg-red-500');
              if (data.state === 'active') {
                currentDot.classList.add('bg-green-500');
              } else if (data.state === 'idle') {
                currentDot.classList.add('bg-gray-400');
              } else {
                currentDot.classList.add('bg-red-500');
              }
            }).catch(console.error);
        }

        setInterval(checkRadStatusGlobal, 3000);
        checkRadStatusGlobal();

        // --- AUTO-LOGOUT SECURITY POLICY ---
        (function () {
          const timeoutMinutes = <?= isset($autoLogoutMinutes) ? $autoLogoutMinutes : 0 ?>;
          if (timeoutMinutes <= 0) return;

          console.log(`Security: Inactivity monitor active (${timeoutMinutes}m). [Robust Timestamp Mode]`);

          let lastActivity = Date.now();
          let isWarningOpen = false;
          const warningThreshold = 60; // 60 seconds before logout

          // Timer tick every 1 second (even if throttled, calculation remains accurate)
          const tick = setInterval(() => {
            const now = Date.now();
            const idleSeconds = Math.floor((now - lastActivity) / 1000);
            const totalTimeout = timeoutMinutes * 60;
            const remaining = totalTimeout - idleSeconds;

            // Show warning if 1 minute left
            if (remaining <= warningThreshold && !isWarningOpen) {
              isWarningOpen = true;
              const modal = document.getElementById('sessionTimeoutModal');
              if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
              }
            }

            // Update countdown in modal
            if (isWarningOpen) {
              const countdownElem = document.getElementById('timeoutCountdown');
              if (countdownElem) countdownElem.textContent = remaining > 0 ? remaining : 0;
            }

            // Forced logout
            if (remaining <= 0) {
              window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=timeout`;
            }
          }, 1000);

          // Events that reset the timer (only if modal is not open)
          const resetEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
          resetEvents.forEach(evt => {
            document.addEventListener(evt, () => {
              if (!isWarningOpen) lastActivity = Date.now();
            }, true);
          });

          // Robust Check mapping to window focus/visibility (Fix for Minimized/Background tabs)
          document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
              const now = Date.now();
              const idleSeconds = Math.floor((now - lastActivity) / 1000);
              const totalTimeout = timeoutMinutes * 60;

              if (idleSeconds >= totalTimeout) {
                window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=timeout`;
              }
            }
          });

          // Modal Action: Stay Logged In
          window.resumeSession = function () {
            isWarningOpen = false;
            idleSeconds = 0;
            const modal = document.getElementById('sessionTimeoutModal');
            if (modal) {
              modal.classList.add('hidden');
              modal.classList.remove('flex');
            }
          };

          // Modal Action: Logout Now
          window.logoutNow = function () {
            window.location.href = `/<?= PROJECT_DIR ?>/logout?reason=manual`;
          };
        })();
      </script>
      <!-- Session Timeout Warning Modal -->
      <div id="sessionTimeoutModal"
        class="hidden fixed inset-0 items-center justify-center p-4 bg-gray-900/90 backdrop-blur-md animate-in fade-in duration-300"
        style="z-index: 2147483647 !important;">
        <div
          class="bg-white w-full max-w-sm rounded-[32px] p-8 shadow-2xl relative overflow-hidden flex flex-col items-center text-center space-y-6">
          <!-- Decoration -->
          <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-50 rounded-full opacity-50"></div>

          <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center text-red-600 shadow-inner">
            <i data-lucide="timer" class="w-8 h-8"></i>
          </div>

          <div class="space-y-2">
            <h3 class="text-xl font-black text-gray-900 tracking-tight">Security Alert</h3>
            <p class="text-sm text-gray-500 leading-relaxed">
              For your protection, your session will expire in <span id="timeoutCountdown"
                class="font-black text-red-600">60</span> seconds due to inactivity.
            </p>
          </div>

          <div class="flex flex-col gap-3 w-full pt-2">
            <button onclick="resumeSession()"
              class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-red-600/20 transition transform active:scale-95">
              Stay Logged In
            </button>
            <button onclick="logoutNow()"
              class="w-full py-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest transition">
              Logout Now
            </button>
          </div>
        </div>
      </div>

      <script>
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }
      </script>
