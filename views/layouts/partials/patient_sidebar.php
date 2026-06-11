<!-- ===== PATIENT MOBILE TOP NAVBAR ===== -->
<header
  class="fixed top-0 left-0 right-0 z-50 border-b shadow-sm flex items-center justify-between px-4 h-14 md:hidden transition-colors duration-300"
  :class="mobileMenuOpen ? 'bg-transparent border-transparent shadow-none' : 'bg-white border-gray-100'">
  <div class="flex items-center gap-2" v-show="!mobileMenuOpen">
    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg hover:bg-gray-100 transition">
      <i data-lucide="menu" class="w-5 h-5 text-gray-700"></i>
    </button>
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="h-8 w-auto" />
    <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($appName) ?></span>
  </div>
  <div class="flex items-center gap-1" v-show="!mobileMenuOpen">
    <!-- Mobile notification bell for patient -->
    <div class="relative" ref="mobileNotifRef">
      <button @click.prevent="toggleNotificationMenu"
        class="relative p-2 rounded-lg hover:bg-gray-100 transition text-gray-600 hover:text-gray-900">
        <i data-lucide="bell" class="w-5 h-5"></i>
        <span v-if="notificationCount > 0"
          class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white leading-none">
          {{ notificationCount > 9 ? '9+' : notificationCount }}
        </span>
      </button>
      <!-- Mobile backdrop for notifications -->
      <div v-if="notificationMenuOpen" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[150] md:hidden"
        @click="notificationMenuOpen = false"></div>

      <!-- Dropdown reuses the same notificationMenuOpen state -->
      <div v-show="notificationMenuOpen" ref="mobileNotificationMenuRef"
        class="fixed md:absolute right-4 md:right-0 top-[60px] md:top-full left-4 md:left-auto w-auto md:w-80 rounded-2xl md:rounded-xl bg-white border border-gray-200 shadow-2xl z-[200] overflow-hidden"
        @click.stop>
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
          <span class="font-semibold text-sm text-gray-800">Notifications</span>
          <div class="flex items-center gap-2">
            <span v-if="notificationCount > 0" class="text-xs text-gray-500">{{ notificationCount }} unread</span>
            <button @click="notificationMenuOpen = false"
              class="p-1 rounded-lg hover:bg-gray-200 transition text-gray-400 hover:text-gray-600">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
        </div>
        <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
          <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
            No new notifications
          </div>
          <div v-for="notif in notifications" :key="notif.id" @click="markAsRead(notif.id, notif.link)"
            class="px-4 py-3 hover:bg-red-50 active:bg-red-100 cursor-pointer transition-colors group border-b border-gray-50 last:border-0">
            <div class="flex items-start gap-3">
              <div
                class="h-8 w-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 group-hover:bg-red-200">
                <i data-lucide="bell" class="w-4 h-4"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-gray-900 group-hover:text-red-700 truncate">{{ notif.title }}</p>
                <p class="text-xs text-gray-600 mt-0.5 leading-snug line-clamp-2">{{ notif.message }}</p>
                <p class="text-[10px] text-gray-400 mt-1 font-medium uppercase tracking-wider">{{ notif.timeAgo }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <!-- Added "Mark all as read" for mobile -->
        <div v-if="notifications.length > 0" class="border-t border-gray-100 p-2 bg-gray-50">
          <button @click="markAllRead"
            class="w-full rounded-md bg-red-600 text-white py-2.5 text-xs font-bold hover:bg-red-700 transition shadow-sm active:scale-[0.98]">
            Mark all as read
          </button>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Mobile slide-out menu for patient -->
<div v-if="mobileMenuOpen" id="mobile-overlay" class="fixed inset-0 bg-black/40 z-40 md:hidden"
  @click="mobileMenuOpen = false"></div>
<div
  class="fixed top-0 left-0 h-full w-64 bg-white shadow-2xl z-50 flex flex-col transition-transform duration-300 md:hidden"
  :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
    <div class="flex items-center gap-2">
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="h-7 w-auto" />
      <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($appName) ?> Portal</span>
    </div>
    <button @click="mobileMenuOpen = false" class="p-1 rounded-lg hover:bg-gray-100">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
  </div>
  <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
    <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href" @click="mobileMenuOpen = false"
      class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition"
      :class="isActive(item.href) ? 'bg-red-50 text-red-700 font-semibold' : 'text-gray-700 hover:bg-gray-100'">
      <i :data-lucide="item.icon" class="w-5 h-5" :class="isActive(item.href) ? 'text-red-600' : 'text-gray-500'"></i>
      {{ item.label }}
    </a>
  </nav>
  <div class="p-4 border-t border-gray-100 relative" ref="mobileProfileMenuRef">
    <!-- Profile Button -->
    <button @click="mobileProfileMenuOpen = !mobileProfileMenuOpen"
      class="w-full flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-gray-100 transition">
      <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center shrink-0 overflow-hidden">
        <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
        <span v-else class="text-red-700 font-bold text-sm" v-text="userInitials"></span>
      </div>
      <div class="flex-1 text-left leading-tight min-w-0">
        <div class="text-sm font-semibold text-gray-900 truncate" v-text="userDisplayName"></div>
        <div class="text-[11px] text-gray-500 truncate" v-text="userEmail"></div>
      </div>
    </button>

    <!-- Profile Dropdown Menu (Mobile) -->
    <div v-show="mobileProfileMenuOpen"
      class="absolute bottom-full left-4 right-4 mb-2 z-50 rounded-2xl bg-gray-800 text-white shadow-2xl border border-white/10 overflow-hidden py-2">
      <div class="px-4 py-3 border-b border-white/10">
        <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
        <div class="text-[10px] text-white/50 uppercase tracking-widest font-bold">Patient Account</div>
      </div>
      <div class="p-1.5 space-y-0.5">
        <button @click="openSettings('appearance')"
          class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
          <i data-lucide="palette" class="w-4 h-4 opacity-70"></i>
          <span>Appearance</span>
        </button>
        <button @click="openSettings('profile')"
          class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
          <i data-lucide="user" class="w-4 h-4 opacity-70"></i>
          <span>Profile</span>
        </button>
        <button @click="openSettings('general')"
          class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-white hover:bg-white/10 rounded-lg transition">
          <i data-lucide="settings" class="w-4 h-4 opacity-70"></i>
          <span>Settings</span>
        </button>
        <div class="my-1 border-t border-white/10"></div>
        <a href="/<?= PROJECT_DIR ?>/logout"
          class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm text-red-300 hover:text-red-200 hover:bg-white/10 rounded-lg transition">
          <i data-lucide="log-out" class="w-4 h-4 opacity-70"></i>
          <span>Log out</span>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Patient DESKTOP sidebar -->
<aside
  class="hidden md:flex fixed left-0 top-0 bg-white border-r px-3 py-6 flex-col h-screen transition-all duration-200 z-50"
  :class="isOpen ? 'w-[275px]' : 'w-20'">
  <button
    class="text-gray-500 text-md absolute top-9 right-2 cursor-w-resize hover:text-red-700 transition sidebar-tooltip"
    @click="toggleSidebar" :data-tooltip="isOpen ? 'Close sidebar' : 'Open sidebar'">
    <!-- panel-left-close icon -->
    <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
      <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
      <line x1="9" x2="9" y1="3" y2="21" />
      <path d="m16 15-3-3 3-3" />
    </svg>
    <!-- panel-left-open icon -->
    <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
      <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
      <line x1="9" x2="9" y1="3" y2="21" />
      <path d="m14 9 3 3-3 3" />
    </svg>
  </button>
  <div class="mb-6 flex items-center border-b border-gray-200 pb-4">
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="h-10 w-auto" />
    <span v-if="isOpen" class="text-sm font-semibold text-gray-600 ml-2 truncate"><?= htmlspecialchars($appName) ?>
      Portal</span>
  </div>
  <nav class="flex-1 space-y-1">
    <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href"
      class="group relative flex items-center rounded-md cursor-pointer transition"
      :class="[isOpen ? 'gap-3 px-5 py-2 justify-start' : 'w-full px-0 py-3 justify-center has-tooltip sidebar-tooltip', isActive(item.href) ? 'bg-red-50 text-red-700 font-semibold' : 'text-gray-700 hover:bg-gray-100']"
      :data-tooltip="!isOpen ? item.label : ''">
      <span v-if="isActive(item.href)" class="absolute right-0 top-0 h-full w-1 bg-red-600"></span>
      <i :data-lucide="item.icon" class="w-5 h-5" :class="isActive(item.href) ? 'text-red-700' : 'text-gray-700'"></i>
      <span v-if="isOpen" class="text-sm truncate">{{ item.label }}</span>
    </a>
  </nav>
  <div class="pt-2 border-t border-gray-300 relative" ref="profileMenuRef">
    <button @click="profileMenuOpen = !profileMenuOpen"
      class="w-full flex items-center gap-3 rounded-xl px-2.5 py-3 transition"
      :class="profileMenuOpen ? 'bg-gray-100 ring-1 ring-gray-200' : 'hover:bg-gray-100'"
      :data-tooltip="!isOpen ? 'Profile' : ''" :class="!isOpen ? 'has-tooltip sidebar-tooltip' : ''">
      <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center shrink-0 overflow-hidden">
        <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
        <span v-else class="text-red-700 font-semibold text-sm" v-text="userInitials"></span>
      </div>
      <div v-if="isOpen" class="flex-1 text-left leading-tight min-w-0">
        <div class="text-sm font-semibold text-gray-900 dark-text-main truncate" v-text="userDisplayName"></div>
        <div class="text-xs text-gray-500 truncate" v-text="userEmail"></div>
      </div>
    </button>
    <div v-if="profileMenuOpen"
      class="absolute z-50 w-64 rounded-2xl bg-gray-800 text-white shadow-xl border border-white/10 overflow-hidden"
      :class="isOpen ? 'left-0 bottom-full mb-2' : 'left-full ml-2 bottom-0'">
      <div class="px-4 py-3 border-b border-white/10 overflow-hidden">
        <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
        <div class="text-xs text-white/60 truncate">Patient</div>
      </div>
      <div class="py-2">
        <button @click="openSettings('appearance')"
          class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
          <i data-lucide="palette" class="text-base opacity-90"></i>
          <span>Appearance</span>
        </button>
        <button @click="openSettings('profile')"
          class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
          <i data-lucide="user" class="text-base opacity-90"></i>
          <span>Profile</span>
        </button>
        <button @click="openSettings('general')"
          class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-white hover:bg-white/10 transition">
          <i data-lucide="settings" class="text-base opacity-90"></i>
          <span>Settings</span>
        </button>
        <div class="my-2 border-t border-white/10"></div>
        <a href="/<?= PROJECT_DIR ?>/logout"
          class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-red-300 hover:text-red-200 hover:bg-white/10 transition">
          <i data-lucide="log-out" class="text-base opacity-90"></i>
          <span>Log out</span>
        </a>
      </div>
    </div>
  </div>
</aside>