<!-- âœ… Vue production local asset -->
<script type="text/javascript" src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/vue.global.prod.js"></script>

<!-- âœ… Lucide production local asset -->
<script type="text/javascript" src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/lucide.min.js"></script>

<!-- âœ… Inject PHP data -->
<script>
  window.__APP__ = {
    role: <?= json_encode($role) ?>,
    userId: <?= json_encode($_SESSION['user_id'] ?? null) ?>,
    menuItems: <?= json_encode($menuItems) ?>,
    currentPath: <?= json_encode($currentPath) ?>,
    basePath: <?= json_encode((strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? $basePath : "") ?>,
    userDisplayName: <?= json_encode($userDisplayName) ?>,
    userEmail: <?= json_encode($userEmail) ?>,
    userInitials: <?= json_encode($initials) ?>,
    userAvatar: <?= json_encode($userAvatar) ?>,
    userSignature: <?= json_encode($userSignature) ?>,
    userProfessionalTitle: <?= json_encode($userProfessionalTitle) ?>,
    userFullNameReport: <?= json_encode($userFullNameReport) ?>,
    userIsAvailable: <?= json_encode((bool) $userIsAvailable) ?>,
    userFirstName: <?= json_encode($userFirstName) ?>,
    userLastName: <?= json_encode($userLastName) ?>,
    userBirthdate: <?= json_encode($userBirthdate) ?>,
    userSex: <?= json_encode($userSex) ?>,
    userContactNumber: <?= json_encode($userContactNumber) ?>,
    userHomeAddress: <?= json_encode($userHomeAddress ?? '') ?>
  };
</script>

<!-- Universal Navigation History Tracker & Smart Back Engine -->
<script>
(function () {
  const STORAGE_KEY = 'citilife_nav_history';
  const MAX_HISTORY = 35;

  function normalizeUrl(urlStr) {
    try {
      if (!urlStr) return '';
      const u = new URL(urlStr, window.location.origin);
      return u.origin + u.pathname + u.search;
    } catch (e) {
      return urlStr || '';
    }
  }

  function isCleanPage(urlStr) {
    try {
      if (!urlStr) return false;
      const u = new URL(urlStr, window.location.origin);
      const path = u.pathname.toLowerCase();
      if (path.includes('login') || path.includes('logout') || path.includes('api/') ||
          path.includes('print-report') ||
          u.searchParams.has('ajax_polling') || u.searchParams.has('ajax')) {
        return false;
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  function trackNavHistory() {
    try {
      const currentHref = normalizeUrl(window.location.href);
      if (!isCleanPage(currentHref)) return;

      let stack = [];
      try {
        stack = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        if (!Array.isArray(stack)) stack = [];
      } catch (e) {
        stack = [];
      }

      // Check if we just navigated backwards via citilifeBack
      const isNavigatingBack = sessionStorage.getItem('citilife_nav_is_back');
      if (isNavigatingBack) {
        sessionStorage.removeItem('citilife_nav_is_back');
        const existingIdx = stack.lastIndexOf(currentHref);
        if (existingIdx !== -1) {
          stack = stack.slice(0, existingIdx + 1);
        } else {
          stack.push(currentHref);
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(stack));
        return;
      }

      // If currentHref is already the top of the stack (e.g. reload or parameter sync)
      if (stack.length > 0 && stack[stack.length - 1] === currentHref) {
        return;
      }

      // If currentHref already exists earlier in the stack (e.g. user navigated back via browser button or link)
      const existingIdx = stack.lastIndexOf(currentHref);
      if (existingIdx !== -1) {
        // Truncate the stack to this point, discarding all child pages that were visited after
        stack = stack.slice(0, existingIdx + 1);
      } else {
        stack.push(currentHref);
        if (stack.length > MAX_HISTORY) {
          stack.shift();
        }
      }

      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(stack));
    } catch (e) {}
  }

  trackNavHistory();

  // Hook replaceState to sync route modifications (e.g. tabs or search)
  try {
    const _origReplaceState = window.history.replaceState;
    window.history.replaceState = function () {
      _origReplaceState.apply(this, arguments);
      trackNavHistory();
    };
  } catch (e) {}

  window.citilifeBack = function (fallbackUrl) {
    try {
      const currentHref = normalizeUrl(window.location.href);
      const currentUrl = new URL(currentHref);

      // 1. Explicit return parameters in URL (?return_url=... or ?back_url=...)
      const explicitBack = currentUrl.searchParams.get('return_url') || currentUrl.searchParams.get('back_url');
      if (explicitBack) {
        try {
          const parsed = new URL(explicitBack, window.location.origin);
          if (parsed.origin === window.location.origin && isCleanPage(parsed.href)) {
            sessionStorage.setItem('citilife_nav_is_back', '1');
            window.location.href = parsed.href;
            return;
          }
        } catch (e) {}
      }

      // 2. Scan sessionStorage history stack going backwards for a distinct previous page
      let targetUrl = null;
      let targetIndex = -1;
      try {
        let stack = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        if (Array.isArray(stack) && stack.length > 0) {
          for (let i = stack.length - 1; i >= 0; i--) {
            const item = stack[i];
            if (item && item !== currentHref) {
              try {
                const itemUrl = new URL(item);
                const isDiff = (itemUrl.pathname !== currentUrl.pathname) || (itemUrl.search !== currentUrl.search);
                if (itemUrl.origin === currentUrl.origin && isDiff && isCleanPage(item)) {
                  targetUrl = item;
                  targetIndex = i;
                  break;
                }
              } catch (err) {}
            }
          }

          if (targetUrl && targetIndex !== -1) {
            // Truncate the stack to the target page so subsequent backs go further up the chain
            const newStack = stack.slice(0, targetIndex + 1);
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(newStack));
            sessionStorage.setItem('citilife_nav_is_back', '1');
            window.location.href = targetUrl;
            return;
          }
        }
      } catch (e) {}

      // 3. Fallback to button's explicit fallbackUrl/href if provided
      if (fallbackUrl && fallbackUrl !== 'javascript:void(0)' && fallbackUrl !== '#' && isCleanPage(fallbackUrl)) {
        try {
          const fbParsed = new URL(fallbackUrl, window.location.origin);
          if (fbParsed.origin === window.location.origin) {
            const isDiff = (fbParsed.pathname !== currentUrl.pathname) || (fbParsed.search !== currentUrl.search);
            if (isDiff) {
              sessionStorage.setItem('citilife_nav_is_back', '1');
              window.location.href = fbParsed.href;
              return;
            }
          }
        } catch (e) {}
      }

      // 4. Role-specific last table/queue URL fallback
      try {
        const lastTable = sessionStorage.getItem('radtech_last_table_url') || sessionStorage.getItem('Citilife_last_worklist_url');
        if (lastTable && lastTable !== currentHref && isCleanPage(lastTable)) {
          sessionStorage.setItem('citilife_nav_is_back', '1');
          window.location.href = lastTable;
          return;
        }
      } catch (e) {}

      // 5. Browser history back
      if (window.history.length > 1) {
        window.history.back();
        if (fallbackUrl) {
          setTimeout(() => {
            window.location.href = fallbackUrl;
          }, 350);
        }
        return;
      }
    } catch (err) {
      console.error('citilifeBack error:', err);
    }

    // 6. Ultimate Fallback URL
    if (fallbackUrl && fallbackUrl !== 'javascript:void(0)' && fallbackUrl !== '#') {
      window.location.href = fallbackUrl;
    }
  };

  // Global back button click listener (capture phase ensures reliable execution)
  document.addEventListener('click', function (e) {
    const backBtn = e.target.closest(
      '[data-back-btn], #back-to-worklist-btn, #patient-details-back-btn, ' +
      'a[title="Back"], a[title="Back to Records"], a[aria-label*="back" i], a[href="javascript:history.back()"]'
    );
    if (!backBtn) return;

    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    e.preventDefault();

    // Call inactive ping if present on page
    if (typeof window.sendInactivePing === 'function') {
      try { window.sendInactivePing(); } catch (err) {}
    } else if (typeof sendInactivePing === 'function') {
      try { sendInactivePing(); } catch (err) {}
    }

    const fallback = backBtn.getAttribute('data-fallback') || backBtn.getAttribute('href') || '';
    window.citilifeBack(fallback);
  }, true);
})();
</script>

<!-- âœ… Vue App -->
<script>
  window.addEventListener('error', function (e) {
    console.error("JS Error: " + e.message + " in " + e.filename + " line " + e.lineno);
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
        globalNotificationOptionsOpen: false,
        activeNotificationDropdown: null,
        showUndoToast: false,
        pendingDeleteId: null,
        pendingDeleteItem: null,
        undoTimeout: null,
        undoInterval: null,
        undoTimerCount: 5,
        undoRingOffset: 0,
        notificationCount: 0,
        notifications: [],
        touchStartX: 0,
        touchStartY: 0,
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
        themeMode: localStorage.getItem('citilife_theme') || 'light',
        // RadTech Settings
        userSignature: window.__APP__.userSignature,
        userProfessionalTitle: window.__APP__.userProfessionalTitle,
        userFullNameReport: window.__APP__.userFullNameReport,
        editIsAvailable: window.__APP__.userIsAvailable !== false,
        editFullName: window.__APP__.userFullNameReport || window.__APP__.userDisplayName,
        editProfessionalTitle: window.__APP__.userProfessionalTitle || '',
        signatureFile: null,
        signaturePreview: null,
        savingRadtechSettings: false,
        role: window.__APP__.role,
        // General Settings — Notification Toggles
        notifEmail: localStorage.getItem('citilife_notif_email') !== 'false',
        notifSystem: localStorage.getItem('citilife_notif_system') !== 'false',
        notifSound: localStorage.getItem('citilife_notif_sound') !== 'false',
        toasts: [],
        editFirstName: '',
        editLastName: '',
        editBirthdate: '',
        editSex: 'Male',
        editContactNumber: '',
        editHomeAddress: '',
        editPassword: '',
        editConfirmPassword: '',
        showNewPassword: false,
        showConfirmPassword: false,
        savingPassword: false,
        themeDropdownOpen: false,
        // Chat Settings
        userId: window.__APP__.userId || null,
        lastReceivedMessageId: null,
        chatMenuOpen: false,
        chatSearchQuery: '',
        searchTimeout: null,
        chatSearchFocused: false,
        recentSearches: [],
        unreadMessageCount: 0,
        conversations: [],
        newMessageViewOpen: false,
        staffSearchQuery: '',
        staffSearchResults: [],
        isSearchingStaff: false,
        activeChats: [],
        isGroupMenuOpen: false,
        // Lightbox state
        lightboxOpen: false,
        lightboxImages: [],
        lightboxIndex: 0,
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
      minimizedChats() {
        return this.activeChats.filter(c => c.minimized);
      },
      visibleChats() {
        return this.activeChats.filter(c => !c.minimized);
      },
      bubbleChats() {
        // Visible windows are the first 3 non-minimized chats.
        const visibleIds = this.visibleChats.slice(0, 3).map(c => String(c.id));
        // Bubbles are everything else (minimized chats + overflow chats that didn't fit)
        return this.activeChats.filter(c => !visibleIds.includes(String(c.id)));
      },
      isDark() {
        if (this.themeMode === 'dark') return true;
        if (this.themeMode === 'light') return false;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
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
      },
      filteredConversations() {
        if (!this.chatSearchQuery) return this.conversations;
        const q = this.chatSearchQuery.toLowerCase();
        return this.conversations.filter(c => c.name.toLowerCase().includes(q));
      },
      filteredStaffSearchResults() {
        const existingIds = this.filteredConversations ? this.filteredConversations.map(c => String(c.id)) : [];
        return (this.staffSearchResults || []).filter(staff => !existingIds.includes(String(staff.id)));
      },
      totalUnreadCount() {
        // Sum unread counts from all active (open) chat windows
        const activeUnread = this.activeChats.reduce((sum, c) => sum + (c.unreadCount || 0), 0);
        // For conversations NOT in activeChats, use the API-fetched DB count
        const activeIds = new Set(this.activeChats.map(c => String(c.id)));
        const conversationUnread = this.conversations
          .filter(c => !activeIds.has(String(c.id)))
          .reduce((sum, c) => sum + (parseInt(c.unread_count) || 0), 0);
        return activeUnread + conversationUnread;
      }
    },
    watch: {
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

      // Load theme from localStorage — default to light mode
      this.themeMode = localStorage.getItem('citilife_theme') || 'light';
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
      setInterval(() => this.fetchNotifications(false), 3000); // 3s real-time fetch interval

      if (this.role !== 'patient') {
        this.pollMessages();
        this.searchStaff();
        setInterval(() => this.pollMessages(), 3000); // 3s message polling

        // ── Restore active chat windows from last session ──
        try {
          const saved = JSON.parse(localStorage.getItem('citilife_active_chats') || '[]');
          if (Array.isArray(saved) && saved.length > 0) {
            saved.forEach(meta => {
              this.activeChats.push({
                ...meta,
                messages: [],
                newMessage: '',
                loading: true,
                sending: false,
                unreadCount: 0,
                selectedAttachments: [],
                attachmentPreviews: []
              });
              // Fetch messages for restored chat
              fetch('<?= url("app/Api/messages.php") ?>?action=fetch_chat&contact_id=' + meta.id, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                  const chat = this.activeChats.find(c => c.id == meta.id);
                  if (chat && data.success) {
                    chat.messages = data.messages;
                    chat.loading = false;
                    nextTick(() => {
                      const body = this.$refs['chatBody_' + chat.id];
                      if (body && body[0]) body[0].scrollTop = body[0].scrollHeight;
                    });
                  }
                }).catch(() => {
                  const chat = this.activeChats.find(c => c.id == meta.id);
                  if (chat) chat.loading = false;
                });
            });
          }
        } catch (e) { console.warn('Could not restore chats:', e); }
      }

      // Close profile menu and notifications when clicking outside
      document.addEventListener("mousedown", (e) => {
        const isNotifButton = e.target.closest('[aria-label="Notifications"]') || e.target.closest('button[onclick*="toggleNotificationMenu"]');
        const isChatButton = e.target.closest('[aria-label="Messages"]') || e.target.closest('button[onclick*="toggleChatMenu"]');

        if (this.profileMenuOpen && this.$refs.profileMenuRef && !this.$refs.profileMenuRef.contains(e.target)) {
          if (!isChatButton && !isNotifButton) this.profileMenuOpen = false;
        }
        if (this.mobileProfileMenuOpen && this.$refs.mobileProfileMenuRef && !this.$refs.mobileProfileMenuRef.contains(e.target)) {
          if (!isChatButton && !isNotifButton) this.mobileProfileMenuOpen = false;
        }
        if (this.notificationMenuOpen) {
          const inDesktopNotif = this.$refs.notificationMenuRef && this.$refs.notificationMenuRef.contains(e.target);
          const inMobileNotif = this.$refs.mobileNotificationMenuRef && this.$refs.mobileNotificationMenuRef.contains(e.target);

          if (!inDesktopNotif && !inMobileNotif && !isNotifButton && !isChatButton) {
            this.notificationMenuOpen = false;
            this.globalNotificationOptionsOpen = false;
          }
        }
        if (this.chatMenuOpen) {
          const inDesktopChat = this.$refs.chatMenuRef && this.$refs.chatMenuRef.contains(e.target);
          
          if (!inDesktopChat && !isChatButton && !isNotifButton) {
            this.chatMenuOpen = false;
          }
        }
        if (this.globalNotificationOptionsOpen) {
          const inGlobalBtn = e.target.closest('.global-notif-btn');
          const inGlobalDropdown = e.target.closest('.global-notif-dropdown');
          if (!inGlobalBtn && !inGlobalDropdown) {
            this.globalNotificationOptionsOpen = false;
          }
        }
        if (this.themeDropdownOpen && this.$refs.themeDropdownRef && !this.$refs.themeDropdownRef.contains(e.target)) {
          this.themeDropdownOpen = false;
        }
        if (this.isGroupMenuOpen && !e.target.closest('.group-bubble-container')) {
          this.isGroupMenuOpen = false;
        }
      });

      // Close on Escape key
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          this.profileMenuOpen = false;
          this.mobileProfileMenuOpen = false;
          this.notificationMenuOpen = false;
          this.settingsModalOpen = false;
          this.lightboxOpen = false;
        }
        if (this.lightboxOpen) {
          if (e.key === "ArrowRight") this.nextLightboxImage();
          if (e.key === "ArrowLeft") this.prevLightboxImage();
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
      isImageAttachment(filename) {
        if (!filename) return false;
        return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(filename);
      },
      formatAttachmentUrl(att) {
        if (!att) return '';
        if (att.startsWith('http://') || att.startsWith('https://')) return att;
        const base = (window.__APP__ && window.__APP__.basePath) ? window.__APP__.basePath : '';
        const clean = att.replace(/^\/+/, '').replace(/^app\//, '');
        return (base ? base + '/' : '/') + clean;
      },
      getAttachmentFileName(att) {
        if (!att) return 'Attachment';
        const parts = att.split('/');
        let name = parts[parts.length - 1];
        // Strip any generated unique prefixes (chat_[hex]_, chat_[hex].[digits]_, file.[digits]_, etc.)
        name = name.replace(/^chat_[a-zA-Z0-9]+(?:\.[0-9]+)?_/, '');
        name = name.replace(/^(?:chat_|file\.)[0-9]+_/, '');
        name = name.replace(/^chat_[a-zA-Z0-9]+_/, '');
        return name || 'Attachment';
      },
      getAttachmentExt(att) {
        if (!att) return 'FILE';
        const parts = att.split('.');
        return parts.length > 1 ? parts[parts.length - 1].toUpperCase() : 'FILE';
      },
      openLightbox(chat, clickedMsg) {
        if (!chat || !chat.messages || !Array.isArray(chat.messages)) return;
        // Get all image messages from the chat
        const images = chat.messages.filter(m => m.attachment && this.isImageAttachment(m.attachment));
        if (images.length === 0) return;
        this.lightboxImages = images.map(m => this.formatAttachmentUrl(m.attachment));
        this.lightboxIndex = images.findIndex(m => m.id === (clickedMsg && clickedMsg.id));
        if (this.lightboxIndex === -1) this.lightboxIndex = 0;
        this.lightboxOpen = true;
      },
      nextLightboxImage() {
        if (this.lightboxImages.length === 0) return;
        this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxImages.length;
      },
      prevLightboxImage() {
        if (this.lightboxImages.length === 0) return;
        this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
      },
      toggleChatMenu() {
        this.chatMenuOpen = !this.chatMenuOpen;
        if (this.chatMenuOpen) {
          this.notificationMenuOpen = false;
          this.profileMenuOpen = false;
          this.mobileProfileMenuOpen = false;
          this.pollMessages();
          this.searchStaff();
        }
      },
      formatTimeAgo(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return diffMins + 'm';
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return diffHours + 'h';
        return Math.floor(diffHours / 24) + 'd';
      },
      pollMessages() {
        if (this.role === 'patient') return;

        // Fetch unread count
        fetch('<?= url("app/Api/messages.php") ?>?action=fetch_unread_count', { credentials: 'same-origin' })
          .then(res => res.json())
          .then(data => { if (data.success) this.unreadMessageCount = data.count; })
          .catch(err => console.error(err));

        // Fetch conversations (always, so badge count stays live)
        fetch('<?= url("app/Api/messages.php") ?>?action=fetch_conversations', { credentials: 'same-origin' })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              this.conversations = data.conversations;

              // Sync open active chat windows in real-time (avatar, name, role, initials)
              if (this.activeChats && this.activeChats.length > 0 && Array.isArray(data.conversations)) {
                data.conversations.forEach(conv => {
                  const active = this.activeChats.find(c => String(c.id) === String(conv.id));
                  if (active) {
                    if (conv.avatar !== undefined) active.avatar = conv.avatar;
                    if (conv.name) active.name = conv.name;
                    if (conv.initials) active.initials = conv.initials;
                    if (conv.role) active.role = conv.role;
                  }
                });
                this.saveActiveChats();
              }

              let maxId = this.lastReceivedMessageId;
              let playSound = false;

              data.conversations.forEach(conv => {
                if (conv.latest_message_id) {
                  const msgId = parseInt(conv.latest_message_id);
                  const senderId = String(conv.sender_id);
                  const currentUserId = String(this.userId);

                  if (senderId !== currentUserId) {
                    if (this.lastReceivedMessageId !== null && msgId > this.lastReceivedMessageId) {
                      playSound = true;
                    }
                    maxId = Math.max(maxId || 0, msgId);
                  }
                }
              });

              if (this.lastReceivedMessageId === null) {
                this.lastReceivedMessageId = maxId || 0;
              } else {
                if (playSound && this.notifSound) {
                  this.playNotificationSound();
                }
                if (maxId > this.lastReceivedMessageId) {
                  this.lastReceivedMessageId = maxId;
                }
              }
            }
          })
          .catch(err => console.error(err));

        // Fetch active chats — only append NEW messages to avoid re-render stealing focus
        this.activeChats.forEach(chat => {
          const markReadParam = chat.minimized ? '0' : '1';
          fetch('<?= url("app/Api/messages.php") ?>?action=fetch_chat&contact_id=' + chat.id + '&mark_read=' + markReadParam, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                // 1. Sync read statuses in real-time (so "Sent" changes to "Seen" instantly)
                const minLength = Math.min(chat.messages.length, data.messages.length);
                for (let i = 0; i < minLength; i++) {
                  if (chat.messages[i].id === data.messages[i].id && chat.messages[i].is_read != data.messages[i].is_read) {
                    chat.messages[i].is_read = data.messages[i].is_read;
                  }
                }

                // 2. Append genuinely new messages
                if (data.messages.length > chat.messages.length) {
                  // Only push the genuinely new messages (avoid full array replacement)
                  const existingIds = new Set(chat.messages.map(m => m.id));
                  const newMsgs = data.messages.filter(m => !existingIds.has(m.id));
                  if (newMsgs.length > 0) {
                    newMsgs.forEach(m => chat.messages.push(m));
                    chat.loading = false;

                    // Count unread: messages from the OTHER person while chat is minimized
                    const incomingFromOther = newMsgs.filter(m => String(m.sender_id) !== String(this.userId));
                    if (incomingFromOther.length > 0 && chat.minimized) {
                      chat.unreadCount = (chat.unreadCount || 0) + incomingFromOther.length;
                    }

                    // Play sound if any incoming messages are from other person
                    if (incomingFromOther.length > 0) {
                      let maxChatMsgId = this.lastReceivedMessageId;
                      let playChatSound = false;
                      incomingFromOther.forEach(m => {
                        const msgId = parseInt(m.id);
                        if (this.lastReceivedMessageId !== null && msgId > this.lastReceivedMessageId) {
                          playChatSound = true;
                        }
                        maxChatMsgId = Math.max(maxChatMsgId || 0, msgId);
                      });

                      if (this.lastReceivedMessageId === null) {
                        this.lastReceivedMessageId = maxChatMsgId || 0;
                      } else {
                        if (playChatSound && this.notifSound) {
                          this.playNotificationSound();
                        }
                        if (maxChatMsgId > this.lastReceivedMessageId) {
                          this.lastReceivedMessageId = maxChatMsgId;
                        }
                      }
                    }

                    // Auto-scroll only if not minimized
                    if (!chat.minimized) {
                      nextTick(() => {
                        const body = this.$refs['chatBody_' + chat.id];
                        if (body && body[0]) {
                          body[0].scrollTop = body[0].scrollHeight;
                        }
                      });
                    }
                  }
                }
              } // Close if (data.success)
            }).catch(err => console.error(err));
        });
      },
      openNewMessageModal() {
        this.newMessageViewOpen = true;
        this.staffSearchQuery = '';
        this.staffSearchResults = [];
        this.searchStaff();
      },
      searchStaff() {
        this.isSearchingStaff = true;
        const currentQuery = this.staffSearchQuery;
        const cacheBuster = '&_t=' + new Date().getTime();
        fetch('<?= url("app/Api/messages.php") ?>?action=search_staff&q=' + encodeURIComponent(currentQuery) + cacheBuster, { credentials: 'same-origin' })
          .then(res => res.json())
          .then(data => {
            if (this.staffSearchQuery !== currentQuery) return;
            this.isSearchingStaff = false;
            if (data.success) this.staffSearchResults = data.staff;
          }).catch(err => {
            if (this.staffSearchQuery !== currentQuery) return;
            this.isSearchingStaff = false;
            console.error(err);
          });
      },
      startNewChat(staff) {
        this.newMessageViewOpen = false;
        this.chatMenuOpen = false;
        this.openChatWindow({
          id: staff.id,
          name: staff.name,
          initials: staff.initials,
          avatar: staff.avatar,
          role: staff.role
        });
      },
      addToRecentSearches(conv) {
        const existingIndex = this.recentSearches.findIndex(c => c.id === conv.id);
        if (existingIndex > -1) {
          this.recentSearches.splice(existingIndex, 1);
        }
        this.recentSearches.unshift(conv);
        if (this.recentSearches.length > 5) {
          this.recentSearches.pop();
        }
      },
      removeRecentSearch(index) {
        this.recentSearches.splice(index, 1);
      },
      onChatSearchFocus() {
        this.chatSearchFocused = true;
        if (!this.chatSearchQuery) {
          this.staffSearchQuery = '';
          this.searchStaff();
        }
      },
      onChatSearchInput() {
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
          const val = this.chatSearchQuery;
          if (val && val.trim() !== '') {
            this.staffSearchQuery = val;
            this.searchStaff();
          } else {
            this.staffSearchQuery = '';
            this.searchStaff();
          }
        }, 300);
      },
      toggleChatMinimize(chat) {
        chat.minimized = !chat.minimized;
        // Clear unread badge when user opens the chat
        if (!chat.minimized) {
          chat.unreadCount = 0;
          fetch('<?= url("app/Api/messages.php") ?>?action=mark_chat_read&contact_id=' + chat.id, { credentials: 'same-origin' }).catch(() => { });
          nextTick(() => {
            const body = this.$refs['chatBody_' + chat.id];
            if (body && body[0]) {
              body[0].scrollTop = body[0].scrollHeight;
            }
          });
        }
        this.saveActiveChats();
      },
      startVoiceCall(chat) {
        alert('Voice calling with ' + (chat.name || 'user') + ' will be available in the upcoming communications release.');
      },
      startVideoCall(chat) {
        alert('Video calling with ' + (chat.name || 'user') + ' will be available in the upcoming communications release.');
      },
      openChatWindow(conv) {
        this.addToRecentSearches(conv);
        const existing = this.activeChats.find(c => c.id == conv.id);
        if (existing) {
          existing.minimized = false;
          existing.unreadCount = 0; // Clear badge when user opens it
          if (conv.avatar !== undefined) existing.avatar = conv.avatar;
          if (conv.name) existing.name = conv.name;
          if (conv.initials) existing.initials = conv.initials;
          if (conv.role) existing.role = conv.role;
          fetch('<?= url("app/Api/messages.php") ?>?action=mark_chat_read&contact_id=' + existing.id, { credentials: 'same-origin' }).catch(() => { });
          this.bringChatToFront(existing);
        } else {
          this.activeChats.unshift({
            ...conv,
            messages: [],
            newMessage: '',
            minimized: false,
            loading: true,
            sending: false,
            unreadCount: 0,
            selectedAttachments: [],
            attachmentPreviews: []
          });

          fetch('<?= url("app/Api/messages.php") ?>?action=fetch_chat&contact_id=' + conv.id, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
              const chat = this.activeChats.find(c => c.id === conv.id);
              if (chat && data.success) {
                chat.messages = data.messages;
                chat.loading = false;
                nextTick(() => {
                  const body = this.$refs['chatBody_' + chat.id];
                  if (body && body[0]) {
                    body[0].scrollTop = body[0].scrollHeight;
                  }
                });
              }
            }).catch(err => console.error(err));
        }
        this.chatMenuOpen = false;
        this.saveActiveChats();
      },
      triggerChatAttachment(chatId) {
        const fileInput = this.$refs['chatAttachment_' + chatId];
        if (fileInput && fileInput[0]) {
          fileInput[0].click();
        }
      },
      handleChatAttachment(chat, event) {
        const files = Array.from(event.target.files);
        if (files.length > 0) {
          if (!chat.selectedAttachments) chat.selectedAttachments = [];
          if (!chat.attachmentPreviews) chat.attachmentPreviews = [];

          let currentTotalSize = chat.selectedAttachments.reduce((sum, f) => sum + f.size, 0);
          let newFilesSize = files.reduce((sum, f) => sum + f.size, 0);

          if (currentTotalSize + newFilesSize > 25 * 1024 * 1024) {
            alert("Total file size exceeds 25MB limit.");
            return;
          }

          files.forEach(file => {
            chat.selectedAttachments.push(file);

            // Create preview
            if (file.type.startsWith('image/')) {
              const reader = new window.FileReader();
              reader.onload = e => {
                chat.attachmentPreviews.push({ name: file.name, url: e.target.result, isImage: true });
              };
              reader.readAsDataURL(file);
            } else {
              chat.attachmentPreviews.push({ name: file.name, url: null, isImage: false });
            }
          });

          // Reset input so same files can be selected again if needed
          event.target.value = '';
        }
      },
      removeChatAttachment(chat, index) {
        if (chat.selectedAttachments && chat.selectedAttachments.length > index) {
          chat.selectedAttachments.splice(index, 1);
          chat.attachmentPreviews.splice(index, 1);
        }
      },
      removeAllChatAttachments(chat) {
        chat.selectedAttachments = [];
        chat.attachmentPreviews = [];
        const fileInput = this.$refs['chatAttachment_' + chat.id];
        if (fileInput && fileInput[0]) {
          fileInput[0].value = ''; // Reset input
        }
      },
      bringChatToFront(chat) {
        if (!chat) return;
        try {
          this.isGroupMenuOpen = false;
          const targetId = String(chat.id);
          const idx = this.activeChats.findIndex(c => String(c.id) === targetId);

          if (idx > -1) {
            this.activeChats[idx].minimized = false;
            this.activeChats[idx].unreadCount = 0; // Clear badge when brought to front
            fetch('<?= url("app/Api/messages.php") ?>?action=mark_chat_read&contact_id=' + targetId, { credentials: 'same-origin' }).catch(() => { });
            if (idx > 0) {
              const movedChat = this.activeChats.splice(idx, 1)[0];
              this.activeChats.unshift(movedChat);
            }
            // Force absolute reactivity refresh in ALL cases
            this.activeChats = [...this.activeChats];

            // Ensure scroll is at the bottom after window re-opens
            nextTick(() => {
              const body = this.$refs['chatBody_' + targetId];
              if (body && body[0]) {
                body[0].scrollTop = body[0].scrollHeight;
              }
            });
          } else {
            chat.minimized = false;
            nextTick(() => {
              const body = this.$refs['chatBody_' + chat.id];
              if (body && body[0]) {
                body[0].scrollTop = body[0].scrollHeight;
              }
            });
          }
        } catch (e) {
          console.error("bringChatToFront error:", e);
        }
      },
      toggleReaction(msg) {
        const reactions = ['😂', '❤️', '👍', '😮', '😢', '🙏'];
        if (!msg.reaction) {
          msg.reaction = '😂';
        } else {
          const idx = reactions.indexOf(msg.reaction);
          if (idx !== -1 && idx < reactions.length - 1) {
            msg.reaction = reactions[idx + 1];
          } else {
            msg.reaction = null;
          }
        }
      },
      setReply(chat, msg) {
        const text = (msg.message || '').slice(0, 20);
        chat.newMessage = 'Replying: ' + text + (text.length >= 20 ? '... ' : ' ');
      },
      getBubbleRadius(chat, msg, msgIndex) {
        if (!chat.messages) return 'border-radius: 18px;';
        const isOutgoing = (msg.sender_id == this.userId);
        const prevMsg = chat.messages[msgIndex - 1];
        const nextMsg = chat.messages[msgIndex + 1];
        const isFirst = !prevMsg || prevMsg.sender_id != msg.sender_id;
        const isLast = !nextMsg || nextMsg.sender_id != msg.sender_id;

        if (isFirst && isLast) return 'border-radius: 18px;';
        if (isOutgoing) {
          if (isFirst) return 'border-radius: 18px 18px 4px 18px;';
          if (isLast) return 'border-radius: 18px 4px 18px 18px;';
          return 'border-radius: 18px 4px 4px 18px;';
        } else {
          if (isFirst) return 'border-radius: 18px 18px 18px 4px;';
          if (isLast) return 'border-radius: 4px 18px 18px 18px;';
          return 'border-radius: 4px 18px 18px 4px;';
        }
      },
      closeChatWindow(chat) {
        this.activeChats = this.activeChats.filter(c => c.id != chat.id);
        this.saveActiveChats();
      },
      toggleChatMinimize(chat) {
        chat.minimized = !chat.minimized;
        this.saveActiveChats();
      },
      saveActiveChats() {
        try {
          const toSave = this.activeChats.map(c => ({
            id: c.id,
            name: c.name,
            initials: c.initials,
            avatar: c.avatar,
            role: c.role,
            minimized: c.minimized
          }));
          localStorage.setItem('citilife_active_chats', JSON.stringify(toSave));
        } catch (e) { console.warn('Could not save chats:', e); }
      },
      scrollToBottom(chat) {
        this.$nextTick(() => {
          const body = this.$refs['chatBody_' + chat.id];
          if (body && body[0]) {
            body[0].scrollTop = body[0].scrollHeight;
          }
        });
      },
      sendMessage(chat) {
        const hasAttachments = chat.selectedAttachments && chat.selectedAttachments.length > 0;
        if ((!chat.newMessage.trim() && !hasAttachments) || chat.sending) return;
        chat.sending = true;

        const messageText = chat.newMessage;
        const files = hasAttachments ? chat.selectedAttachments : [null];

        const sendPromises = files.map((file, index) => {
          const formData = new window.FormData();
          formData.append('action', 'send_message');
          formData.append('contact_id', chat.id);
          // Only attach the text message to the first request
          formData.append('message', index === 0 ? messageText : '');
          if (file) {
            formData.append('attachment', file);
          }

          return fetch('<?= url("app/Api/messages.php") ?>', {
            method: 'POST', credentials: 'same-origin',
            body: formData
          }).then(res => res.json());
        });

        Promise.all(sendPromises).then(results => {
          chat.sending = false;
          let hasError = false;

          results.forEach(data => {
            if (data.success) {
              chat.messages.push(data.message);
            } else {
              hasError = true;
              console.error(data.error);
            }
          });

          if (hasError) {
            alert('Some messages/attachments failed to send.');
          }

          chat.newMessage = '';
          this.removeAllChatAttachments(chat);
          nextTick(() => {
            const body = this.$refs['chatBody_' + chat.id];
            if (body && body[0]) {
              body[0].scrollTop = body[0].scrollHeight;
            }
          });
          this.pollMessages();
        }).catch(err => {
          chat.sending = false;
          console.error(err);
        });
      },
      requestPasswordReset() {
        if (this.isRequestingReset) return;
        if (!this.userEmail) {
          this.showToast('Error', 'No email associated with your account.', 'error');
          return;
        }

        this.isRequestingReset = true;
        const formData = new FormData();
        formData.append('email', this.userEmail);

        fetch('<?= url("app/Api/request_password_reset.php") ?>', {
          method: 'POST', credentials: 'same-origin',
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
          this.editHomeAddress = window.__APP__.userHomeAddress || '';

          const fullName = ((this.editFirstName || '') + ' ' + (this.editLastName || '')).trim();
          if (!this.editDisplayName || this.editDisplayName === fullName) {
            this.editDisplayName = this.editFirstName || '';
          }
        }
        this.editPassword = '';
        this.editConfirmPassword = '';
        this.showNewPassword = false;
        this.showConfirmPassword = false;

        this.settingsModalOpen = true;
        nextTick(() => {
          this.renderIcons();
          if (this.role === 'patient') {
            const birthdateInput = document.getElementById('settingsBirthdate');
            if (birthdateInput && typeof ModernDatePicker !== 'undefined') {
              if (birthdateInput._customDatePicker) {
                birthdateInput._customDatePicker.setDate(this.editBirthdate || '');
              } else {
                new ModernDatePicker(birthdateInput, {
                  maxDate: new Date(),
                  onSelect: (val) => {
                    this.editBirthdate = val;
                  }
                });
              }

              if (!birthdateInput._hasDatepickerListener) {
                birthdateInput.addEventListener('changeDate', () => {
                  this.editBirthdate = birthdateInput.value;
                });
                birthdateInput.addEventListener('change', () => {
                  this.editBirthdate = birthdateInput.value;
                });
                birthdateInput._hasDatepickerListener = true;
              }
            }
          }
        });
      },
      selectSettingsTab(tab) {
        this.settingsActiveTab = tab;
        nextTick(() => this.renderIcons());
      },
      openEditProfileModal() {
        this.openSettings('profile');
      },
      openPersonalizationModal() {
        this.openSettings('general');
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
        formData.append('is_available', this.editIsAvailable ? 1 : 0);
        if (this.signatureFile) {
          formData.append('signature', this.signatureFile);
        }

        fetch('<?= url("app/Api/update_profile.php") ?>', {
          method: 'POST', credentials: 'same-origin',
          body: formData
        })
          .then(async res => {
            const text = await res.text();
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Server response:', text);
              throw new Error('Server returned an invalid response. Please try again.');
            }
          })
          .then(data => {
            this.savingRadtechSettings = false;
            if (data.success) {
              // UPDATE ONLY REPORT-SPECIFIC STATE
              this.userFullNameReport = data.full_name_report;
              this.userProfessionalTitle = data.professional_title;
              window.__APP__.userFullNameReport = data.full_name_report;
              window.__APP__.userProfessionalTitle = data.professional_title;

              if (data.is_available !== undefined) {
                window.__APP__.userIsAvailable = data.is_available;
                this.editIsAvailable = data.is_available;
              }

              if (data.signature) {
                this.userSignature = data.signature;
                window.__APP__.userSignature = data.signature;
              }
              this.signatureFile = null;
              this.signaturePreview = null;
              this.settingsModalOpen = false;

              if (window.showSuccess) {
                showSuccess('Report settings updated!');
              } else {
                alert('Report settings updated!');
              }
            } else {
              if (typeof window.toast === 'function') {
                window.toast(data.error || 'Failed to update settings.', 'error');
              } else {
                alert(data.error || 'Failed to update settings.');
              }
            }
          })
          .catch(err => {
            console.error(err);
            this.savingRadtechSettings = false;
            alert(err.message || 'A network error occurred.');
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
      toggleAvailability() {
        const formData = new window.FormData();
        formData.append('action', 'update_radtech_settings');
        formData.append('report_full_name', this.editFullName);
        formData.append('professional_title', this.editProfessionalTitle);
        formData.append('is_available', this.editIsAvailable ? 1 : 0);

        fetch('<?= url("app/Api/update_profile.php") ?>', {
          method: 'POST', credentials: 'same-origin',
          body: formData
        })
          .then(async res => {
            const text = await res.text();
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Server response:', text);
              throw new Error('Server returned an invalid response.');
            }
          })
          .then(data => {
            if (data.success) {
              if (data.is_available !== undefined) {
                window.__APP__.userIsAvailable = data.is_available;
                this.editIsAvailable = data.is_available;
              }
              if (window.showSuccess) {
                showSuccess(this.editIsAvailable ? 'You are now marked as available.' : 'You are now marked as unavailable.');
              }
            } else {
              alert(data.error || 'Failed to update availability.');
              this.editIsAvailable = !this.editIsAvailable; // Revert on failure
            }
          })
          .catch(err => {
            console.error(err);
            alert(err.message || 'A network error occurred.');
            this.editIsAvailable = !this.editIsAvailable; // Revert on failure
          });
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
      toggleTheme() {
        const next = this.isDark ? 'light' : 'dark';
        this.setTheme(next);
      },
      requestEmailChange() {
        this.emailChangeState = 'sending';
        fetch('<?= url("app/Api/send_email_change_otp.php") ?>', { method: 'POST', credentials: 'same-origin' })
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
        fetch('<?= url("app/Api/verify_email_change_otp.php") ?>', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ otp: this.otpCode })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              this.emailChangeState = 'editable';
              if (window.showSuccess) showSuccess('Email verified. You can now change it.');
            } else {
              if (typeof window.toast === 'function') window.toast(data.error || 'Invalid OTP.', 'error');
              const otpEl = document.querySelector('input[v-model="otpCode"]');
              if (otpEl && window.FormValidator) window.FormValidator.showError(otpEl, data.error || 'Invalid OTP.');
            }
          })
          .catch(err => {
            console.error(err);
            if (typeof window.toast === 'function') window.toast('Network error occurred.', 'error');
          });
      },
      saveProfile() {
        if (this.role === 'patient') {
          if (!this.editFirstName || !this.editLastName || !this.editEmail || !this.editBirthdate || !this.editContactNumber) {
            if (typeof window.toast === 'function') window.toast('Please fill out all required fields.', 'error');
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
          formData.append('display_name', this.editDisplayName || '');
          formData.append('first_name', this.editFirstName);
          formData.append('last_name', this.editLastName);
          formData.append('birthdate', this.editBirthdate);
          formData.append('sex', this.editSex);
          formData.append('contact_number', this.editContactNumber);
          formData.append('home_address', this.editHomeAddress);
        } else {
          formData.append('system_name', this.editDisplayName);
        }

        if (this.uploadFile) {
          formData.append('avatar', this.uploadFile);
        }

        fetch('<?= url("app/Api/update_profile.php") ?>', {
          method: 'POST', credentials: 'same-origin',
          body: formData
        })
          .then(async res => {
            const text = await res.text();
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Server response:', text);
              throw new Error('Server returned an invalid response. Please try again.');
            }
          })
          .then(data => {
            this.savingProfile = false;
            if (data.success) {
              this.userDisplayName = data.name;
              window.__APP__.userDisplayName = data.name;
              this.userEmail = data.email;
              window.__APP__.userEmail = data.email;
              this.userInitials = data.initials;
              if (data.avatar) {
                let cleanAvatar = data.avatar;
                if (cleanAvatar.startsWith('/app/public/')) {
                  cleanAvatar = cleanAvatar.replace('/app/public/', '/public/');
                }
                const cacheBuster = cleanAvatar.includes('?') ? '&t=' + Date.now() : '?t=' + Date.now();
                this.userAvatar = cleanAvatar + cacheBuster;
                window.__APP__.userAvatar = this.userAvatar;
              }
              this.uploadFile = null;
              this.uploadPreview = null;

              if (this.role === 'patient') {
                window.__APP__.userFirstName = data.first_name;
                window.__APP__.userLastName = data.last_name;
                window.__APP__.userBirthdate = data.birthdate;
                window.__APP__.userSex = data.sex;
                window.__APP__.userContactNumber = data.contact_number;
                window.__APP__.userHomeAddress = data.home_address;

                this.editDisplayName = data.name;
                this.editFirstName = data.first_name;
                this.editLastName = data.last_name;
                this.editBirthdate = data.birthdate;
                this.editSex = data.sex;
                this.editContactNumber = data.contact_number;
                this.editHomeAddress = data.home_address;
              }

              this.settingsModalOpen = false;
              if (window.showSuccess) {
                showSuccess('Profile updated successfully!');
              } else {
                alert('Profile updated successfully!');
              }
            } else {
              alert(data.error || 'Failed to update profile.');
            }
          })
          .catch(err => {
            console.error(err);
            this.savingProfile = false;
            alert(err.message || 'A network error occurred.');
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

        fetch('<?= url("app/Api/update_profile.php") ?>', {
          method: 'POST', credentials: 'same-origin',
          body: formData
        })
          .then(async res => {
            const text = await res.text();
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Server response:', text);
              throw new Error('Server returned an invalid response.');
            }
          })
          .then(data => {
            this.savingPassword = false;
            if (data.success) {
              this.editPassword = '';
              this.editConfirmPassword = '';
              this.settingsModalOpen = false;
              if (window.showSuccess) {
                showSuccess('Password updated successfully!');
              } else {
                alert('Password updated successfully!');
              }
            } else {
              if (typeof window.toast === 'function') window.toast(data.error || 'Failed to update password.', 'error');
              const pwInput = document.querySelector('input[v-model="editPassword"]');
              if (pwInput && window.FormValidator) window.FormValidator.showError(pwInput, data.error || 'Failed to update password.');
            }
          })
          .catch(err => {
            console.error(err);
            this.savingPassword = false;
            if (typeof window.toast === 'function') window.toast(err.message || 'A network error occurred.', 'error');
            else alert(err.message || 'A network error occurred.');
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
        if (this.notificationMenuOpen) {
          this.chatMenuOpen = false;
          this.profileMenuOpen = false;
          this.mobileProfileMenuOpen = false;
          nextTick(() => this.renderIcons());
        } else {
          this.globalNotificationOptionsOpen = false;
        }
      },
      closeNotificationMenu() {
        this.notificationMenuOpen = false;
        this.globalNotificationOptionsOpen = false;
      },
      toggleGlobalNotificationOptions() {
        this.globalNotificationOptionsOpen = !this.globalNotificationOptionsOpen;
      },
      fetchNotifications(isInitial = false) {
        fetch('<?= url('app/Api/notifications.php') ?>', { credentials: 'same-origin' })
          .then(res => res.json())
          .then(data => {
            if (!data.error) {
              const oldIds = this.notifications.map(n => String(n.id));
              if (this.pendingDeleteId) {
                data.notifications = data.notifications.filter(n => String(n.id) !== String(this.pendingDeleteId));
              }

              this.notificationCount = data.unread_count;
              this.notifications = data.notifications;

              // Play sound and display toast alerts for new unread notifications
              if (!isInitial && data.notifications.length > 0) {
                const newNotifs = data.notifications.filter(n => !oldIds.includes(String(n.id)) && n.is_read == 0);
                if (newNotifs.length > 0) {
                  if (this.notifSound) {
                    this.playNotificationSound();
                  }
                  if (this.notifSystem) {
                    newNotifs.forEach(n => {
                      const category = this.getNotificationCategory(n);
                      this.showToast(n.title, n.message, category, n.link, n.id);
                    });
                  }
                }
              }

              nextTick(() => this.renderIcons());
            }
          })
          .catch(err => console.error('Error fetching notifications:', err));
      },
      showToast(title, message, type = null, link = '#', notificationId = null) {
        const id = Date.now() + Math.random();
        const finalType = type || this.getNotificationCategory({ title, message });
        this.toasts.push({ id, title, message, type: finalType, link, notificationId });
        nextTick(() => this.renderIcons());
        setTimeout(() => {
          this.dismissToast(id);
        }, 5000); // auto dismiss after 5s
      },
      getNotificationCategory(item) {
        if (!item) return 'info';

        const type = (item.type || '').toLowerCase();
        if (type === 'success') return 'success';
        if (type === 'error' || type === 'danger') return 'danger';
        if (type === 'warning' || type === 'warn') return 'warning';
        if (type === 'purple') return 'purple';

        const title = (item.title || '').toLowerCase();
        const message = (item.message || '').toLowerCase();
        const combined = `${title} ${message}`;

        // 1. SUCCESS / GREEN (Completed, Approved, Released, Resolved, Verified, Success)
        if (
          combined.includes('approved') ||
          combined.includes('released') ||
          combined.includes('resolved') ||
          combined.includes('verified') ||
          combined.includes('success') ||
          (combined.includes('completed') && !combined.includes('reading completed'))
        ) {
          return 'success';
        }

        // 2. VIOLET / PURPLE (Report Ready, Edited Report Ready, Report Updated)
        if (
          combined.includes('report ready') ||
          combined.includes('report updated') ||
          combined.includes('edited report')
        ) {
          return 'purple';
        }

        // 3. DANGER / RED (Rejected, Denied, Cancelled, Lockout, Critical, Failed, Error)
        if (
          combined.includes('reject') ||
          combined.includes('denied') ||
          combined.includes('cancel') ||
          combined.includes('lockout') ||
          combined.includes('critical') ||
          combined.includes('failed') ||
          combined.includes('error')
        ) {
          return 'danger';
        }

        // 4. WARNING / AMBER / ORANGE (Payment Required, Pending Payment, Payment, Overdue, Error Report, Dispute, Escalated, Feedback, Alert, Warning)
        if (
          combined.includes('overdue') ||
          combined.includes('error report') ||
          combined.includes('correction request') ||
          combined.includes('correction requested') ||
          combined.includes('dispute') ||
          combined.includes('escalat') ||
          combined.includes('feedback') ||
          combined.includes('alert') ||
          combined.includes('warning') ||
          combined.includes('payment') ||
          combined.includes('amount due')
        ) {
          return 'warning';
        }

        // 5. INFO / BLUE (New X-ray, Reading, Request, Registration, Account, etc.)
        if (
          combined.includes('x-ray') ||
          combined.includes('xray') ||
          combined.includes('image') ||
          combined.includes('upload') ||
          combined.includes('report') ||
          combined.includes('reading') ||
          combined.includes('request') ||
          combined.includes('registration') ||
          combined.includes('account') ||
          combined.includes('case')
        ) {
          return 'info';
        }

        return 'danger';
      },
      getNotificationCircleClass(item) {
        if (!item) return 'notif-color-read';
        if (item.is_read == 1 || item.is_read === true) {
          return 'notif-color-read';
        }

        const category = this.getNotificationCategory(item);
        if (category === 'success') return 'notif-color-success';
        if (category === 'purple') return 'notif-color-purple';
        if (category === 'danger') return 'notif-color-danger';
        if (category === 'warning') return 'notif-color-warning';
        return 'notif-color-info';
      },
      getToastClass(toast) {
        const category = this.getNotificationCategory(toast);
        if (category === 'success') return 'notif-color-success';
        if (category === 'purple') return 'notif-color-purple';
        if (category === 'danger') return 'notif-color-danger';
        if (category === 'warning') return 'notif-color-warning';
        return 'notif-color-info';
      },
      getToastIcon(toast) {
        if (toast && toast.icon) return toast.icon;
        return 'bell';
      },
      getToastStyle(toast) {
        const category = this.getNotificationCategory(toast);
        if (category === 'success') {
          return { bg: '#d1fae5', color: '#059669' };
        }
        if (category === 'purple') {
          return { bg: '#ede9fe', color: '#7c3aed' };
        }
        if (category === 'danger') {
          return { bg: '#fee2e2', color: '#dc2626' };
        }
        if (category === 'warning') {
          return { bg: '#fef3c7', color: '#d97706' };
        }
        return { bg: '#dbeafe', color: '#2563eb' };
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
          // Throttle to prevent multiple overlaps within 1 second
          const now = Date.now();
          if (window.__lastNotificationSoundTime && (now - window.__lastNotificationSoundTime < 1000)) {
            return;
          }
          window.__lastNotificationSoundTime = now;

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
        this.notificationCount = 0;
        if (Array.isArray(this.notifications)) {
          this.notifications.forEach(n => { n.is_read = 1; });
        }
        fetch('<?= url('app/Api/notifications.php') ?>', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'mark_read' })
        }).then(() => {
          this.fetchNotifications();
        });
      },
      toggleNotificationOptions(id) {
        if (this.activeNotificationDropdown === id) {
          this.activeNotificationDropdown = null;
        } else {
          this.activeNotificationDropdown = id;
        }
      },
      handleTouchStart(e, id) {
        if (e.changedTouches && e.changedTouches.length > 0) {
          this.touchStartX = e.changedTouches[0].screenX;
          this.touchStartY = e.changedTouches[0].screenY;
        }
      },
      handleTouchEnd(e, id) {
        if (e.changedTouches && e.changedTouches.length > 0) {
          let endX = e.changedTouches[0].screenX;
          let endY = e.changedTouches[0].screenY;
          let diffX = this.touchStartX - endX;
          let diffY = Math.abs(this.touchStartY - endY);
          
          if (diffX > 40 && diffY < 30) {
            this.activeNotificationDropdown = id;
          }
        }
      },
      markAsUnread(id) {
        this.activeNotificationDropdown = null;
        fetch('<?= url('app/Api/notifications.php') ?>', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'mark_unread', notification_id: id })
        }).then(() => {
          this.fetchNotifications();
        });
      },
      deleteNotification(id) {
        this.activeNotificationDropdown = null;

        const index = this.notifications.findIndex(n => n.id === id);
        if (index > -1) {
          const item = this.notifications[index];
          this.notifications.splice(index, 1);

          if (this.undoTimeout) {
            clearTimeout(this.undoTimeout);
            if (this.pendingDeleteId) {
              fetch('<?= url('app/Api/notifications.php') ?>', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', notification_id: this.pendingDeleteId }),
                keepalive: true
              });
            }
          }

          this.pendingDeleteId = id;
          this.pendingDeleteItem = item;
          this.showUndoToast = true;
          this.undoTimerCount = 5;
          this.undoRingOffset = 0;
          nextTick(() => {
            this.renderIcons();
            setTimeout(() => {
                this.undoRingOffset = 62.83;
            }, 50);
          });

          if (this.undoInterval) clearInterval(this.undoInterval);
          this.undoInterval = setInterval(() => {
            if (this.undoTimerCount > 1) {
              this.undoTimerCount--;
            }
          }, 1000);

          this.undoTimeout = setTimeout(() => {
            this.showUndoToast = false;
            clearInterval(this.undoInterval);
            if (this.pendingDeleteId === id) {
              fetch('<?= url('app/Api/notifications.php') ?>', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', notification_id: id })
              });
              this.pendingDeleteId = null;
              this.pendingDeleteItem = null;
            }
          }, 5000);
        }
      },
      undoDelete() {
        if (this.pendingDeleteItem) {
          this.showUndoToast = false;
          this.undoRingOffset = 0;
          clearTimeout(this.undoTimeout);
          clearInterval(this.undoInterval);
          this.fetchNotifications();
          this.pendingDeleteId = null;
          this.pendingDeleteItem = null;
        }
      },
      closeUndoToast() {
        this.showUndoToast = false;
        this.undoRingOffset = 0;
        if (this.undoTimeout) {
          clearTimeout(this.undoTimeout);
          clearInterval(this.undoInterval);
          if (this.pendingDeleteId) {
            fetch('<?= url('app/Api/notifications.php') ?>', {
              method: 'POST', credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'delete', notification_id: this.pendingDeleteId })
            });
            this.pendingDeleteId = null;
            this.pendingDeleteItem = null;
          }
        }
      },
      markAsRead(id, link) {
        this.activeNotificationDropdown = null;
        if (link && link !== '#') {
          const notif = this.notifications.find(n => n.id === id);
          let targetHighlight = null;

          // Smartly extract identifier from notification title or message if available
          if (notif) {
            const fullText = (notif.title || '') + ' ' + (notif.message || '');
            const matchBranchCase = fullText.match(/\b([A-Za-z]{2,6}\d{4}-\d{4,6})\b/i);
            const matchParen = fullText.match(/\(([A-Za-z0-9-]+)\)/i);
            const matchReq = fullText.match(/\b(REQ-[A-Za-z0-9-]+)\b/i);
            const matchCas = fullText.match(/\b(CAS-[A-Za-z0-9-]+)\b/i);
            const matchPx = fullText.match(/\b(PX-[A-Za-z0-9-]+|PAT-[A-Za-z0-9-]+)\b/i);
            const matchGeneric = fullText.match(/(?:case|request)\s*[:#(\s]*([A-Za-z0-9-]+)/i);

            if (matchBranchCase) targetHighlight = matchBranchCase[1];
            else if (matchParen) targetHighlight = matchParen[1];
            else if (matchReq) targetHighlight = matchReq[1];
            else if (matchCas) targetHighlight = matchCas[1];
            else if (matchPx) targetHighlight = matchPx[1];
            else if (matchGeneric) targetHighlight = matchGeneric[1];
          }

          let finalUrl;
          try {
            const basePath = '<?= PROJECT_DIR ?>' ? '/' + '<?= PROJECT_DIR ?>' + '/' : '/';
            let cleanLink = link;
            if (!'<?= PROJECT_DIR ?>' && cleanLink && cleanLink.toLowerCase().startsWith('/citilife-system/')) {
              cleanLink = cleanLink.replace(/^\/citilife-system\//i, '/');
            }
            finalUrl = new URL(cleanLink, window.location.origin + basePath);
          } catch (e) {
            finalUrl = new URL(link, window.location.origin);
          }

          // Ensure highlight param exists in URL
          if (!finalUrl.searchParams.has('highlight') && !finalUrl.searchParams.has('highlight_case') && targetHighlight) {
            finalUrl.searchParams.set('highlight', targetHighlight);
          }
          if (notif && notif.is_read == 0) {
            finalUrl.searchParams.set('is_new', '1');
          }

          // Background mark read
          fetch('<?= url('app/Api/notifications.php') ?>', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', notification_id: id }),
            keepalive: true
          });

          // Optimistically update client notification state
          if (notif && notif.is_read == 0) {
            notif.is_read = 1;
            this.notificationCount = Math.max(0, this.notificationCount - 1);
          }

          // Check if destination is the current page
          try {
            const currentUrl = new URL(window.location.href);
            const targetPageParam = finalUrl.searchParams.get('page');
            const currentPageParam = currentUrl.searchParams.get('page');
            const targetPath = finalUrl.pathname.replace(/\/$/, '').split('/').pop() || 'index.php';
            const currentPath = currentUrl.pathname.replace(/\/$/, '').split('/').pop() || 'index.php';

            const isSamePage = (targetPath === currentPath && (targetPageParam || '') === (currentPageParam || '')) ||
                               (finalUrl.pathname === currentUrl.pathname && (targetPageParam || '') === (currentPageParam || ''));

            if (isSamePage) {
              this.notificationMenuOpen = false;
              window.history.replaceState({}, document.title, finalUrl.toString());
              if (window.__APP__) {
                window.__APP__.currentPath = finalUrl.pathname + finalUrl.search;
              }
              const highlightTarget = finalUrl.searchParams.get('highlight') || finalUrl.searchParams.get('highlight_case') || targetHighlight;
              if (typeof window.locateAndHighlight === 'function') {
                const located = window.locateAndHighlight(highlightTarget);
                if (located) return;
              }
            }
          } catch (e) {}

          // Navigate if on a different page or element not found immediately
          window.location.href = finalUrl.toString();
        } else {
          fetch('<?= url('app/Api/notifications.php') ?>', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', notification_id: id })
          }).then(() => {
            this.fetchNotifications();
          });
        }
      },
      isActive(href) {
        try {
          const currentUrl = new window.URL(window.__APP__.currentPath, window.location.origin);
          // Extract current page: prefer ?page= query param, fallback to last path segment
          const _currentPageParam = currentUrl.searchParams.get('page');
          let _currentPathSeg = currentUrl.pathname.replace(/\/$/, '').split('/').pop();
          // Treat index.php as dashboard if no page param is set
          if (!_currentPageParam && (_currentPathSeg === 'index.php' || _currentPathSeg === 'Citilife-System' || _currentPathSeg === '')) {
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
          if (targetPage === 'patient-records' && ['patient-records', 'patient-details', 'patient-history', 'records-history'].includes(currentPage)) {
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
          if (targetPage === 'my-records' && ['my-records', 'case-status', 'view-report', 'download-report', 'feedback'].includes(currentPage)) {
            return true;
          }

          // Radiologist specific associations
          if (currentRole === 'radiologist' || currentRole === 'radtech' || currentRole === 'admin_central' || currentRole === 'branch_admin' || currentRole === 'it_admin') {
            if (targetPage === 'worklist' && ['worklist', 'case-review'].includes(currentPage)) {
              if (currentPage === 'case-review' && currentUrl.searchParams.get('back_to') === 'patient-records-history') {
                return false;
              }
              return true;
            }
            if (targetPage === 'patient-history' && ['patient-history', 'patient-records-history'].includes(currentPage)) {
              return true;
            }
            if (targetPage === 'patient-history' && currentPage === 'case-review' && currentUrl.searchParams.get('back_to') === 'patient-records-history') {
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
  try {
    const vm = app.mount("#app");
    window.vm = vm;
  } catch (e) {
    console.error("Vue Mount Error:", e);
  }

  window.openPatientSettings = function(e) {
    if (e) e.preventDefault();
    if (window.vm && window.vm.openSettings) {
        window.vm.openSettings('profile');
    }
  };
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
              if (newEl) {
                // Allow local scripts to modify newEl before it gets injected (e.g., hiding rows to prevent flicker)
                document.dispatchEvent(new window.CustomEvent('realtime:beforeUpdate', { 
                    detail: { newEl: newEl, el: el } 
                }));
                el.innerHTML = newEl.innerHTML;
              }
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

    fetch('<?= url("app/Api/case_activity.php") ?>?action=status&case_id=' + caseId + '&_t=' + Date.now(), { credentials: 'same-origin' })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;
        const currentDot = document.getElementById('rad-activity-dot');
        if (!currentDot) return;

        currentDot.classList.remove('bg-gray-400', 'bg-green-500', 'bg-red-500', 'animate-pulse');
        if (data.state === 'active') {
          currentDot.classList.add('bg-green-500');
          if (data.is_typing) currentDot.classList.add('animate-pulse');
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
        sessionStorage.clear();
        window.location.href = `<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>logout?reason=timeout`;
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
          sessionStorage.clear();
          window.location.href = `<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>logout?reason=timeout`;
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
      sessionStorage.clear();
      window.location.href = `<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>logout?reason=manual`;
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
  // Fallback: Ensure skeleton hides even if Vue mounting fails
  (function() {
    function hideSkeletonFallback() {
      const fallbackLoader = document.getElementById('app-loading');
      if (fallbackLoader && !fallbackLoader.classList.contains('hidden')) {
        fallbackLoader.classList.add('fade-out');
        setTimeout(() => {
          fallbackLoader.classList.add('hidden');
        }, 280);
      }
    }

    if (document.readyState === 'complete') {
      setTimeout(hideSkeletonFallback, 1000); 
    } else {
      window.addEventListener('load', () => {
        setTimeout(hideSkeletonFallback, 1000);
      });
    }
  })();
</script>

<script>
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Auto-hide flash messages (success/error banners) after 4 seconds
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      const flashMessages = document.querySelectorAll('div.bg-red-50.border-red-300, div.bg-green-50.border-green-300');
      flashMessages.forEach(msg => {
        msg.style.transition = 'opacity 0.5s ease';
        msg.style.opacity = '0';
        setTimeout(() => {
          msg.style.display = 'none';
        }, 500);
      });
    }, 4000);
  });
</script>

<?php
echo '<script src="' . url('views/pages/patient/my-records.js?v=' . time()) . '"></script>';
?>

<script>
  // GLOBAL FAILSAFE: Remove skeleton and v-cloak if Vue fails to mount
  window.addEventListener('load', function() {
    setTimeout(() => {
      const loader = document.getElementById('app-loading');
      if (loader && !loader.classList.contains('hidden')) {
        console.error('Vue failed to mount! Forcing skeleton removal.');
        loader.classList.add('fade-out');
        setTimeout(() => loader.classList.add('hidden'), 280);

        const app = document.getElementById('app');
        if (app && app.hasAttribute('v-cloak')) {
          app.removeAttribute('v-cloak');
        }
      }
    }, 2000);
  });
</script>

<!-- Modern Custom Select Dropdowns Engine, Custom Tooltips & TimePicker -->
<script src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/custom-select.js?v=<?= time() ?>"></script>
<script src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/custom-tooltip.js?v=<?= time() ?>"></script>
<script src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/custom-timepicker.js?v=<?= time() ?>"></script>
<script src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/notification-locator.js?v=<?= time() ?>"></script>

