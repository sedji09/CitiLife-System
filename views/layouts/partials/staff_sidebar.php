<!-- ===== STAFF SIDEBAR (radtech/radiologist/admin) ===== -->
<aside class="fixed left-0 top-0 bg-white border-r px-3 py-6 flex flex-col h-screen transition-all duration-200 z-50"
  :class="isOpen ? 'w-[275px]' : '-translate-x-full md:translate-x-0 md:w-20'"
  :style="{ width: isMobileMenuOpen ? '275px' : (isOpen ? '275px' : '80px'), transform: isMobileMenuOpen ? 'translateX(0)' : (isMobile ? 'translateX(-100%)' : 'translateX(0)') }">
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

  <!-- Header logo-->
  <div class="mb-6 flex items-center border-b border-gray-200 pb-4">
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" class="h-10 w-auto" />
    <span v-if="isOpen" class="text-sm font-semibold text-gray-600 ml-2 truncate">
      <?= htmlspecialchars($appName) ?>
    </span>
  </div>

  <!-- Menu -->
  <nav class="flex-1 space-y-1">
    <a v-for="item in menuItems" :key="item.href" :href="basePath + item.href"
      class="group relative flex items-center rounded-md cursor-pointer transition" :class="[
                isOpen ? 'gap-3 px-5 py-2 justify-start' : 'w-full px-0 py-3 justify-center has-tooltip sidebar-tooltip',
                isActive(item.href)
                ? 'bg-red-50 text-red-700 font-semibold'
                : 'text-gray-700 hover:bg-gray-100'
            ]" :data-tooltip="!isOpen ? item.label : ''">
      <!-- Active right bar -->
      <span v-if="isActive(item.href)" class="absolute right-0 top-0 h-full w-1 bg-red-600"></span>

      <!-- Icon -->
      <i :data-lucide="item.icon" class="w-5 h-5" :class="isActive(item.href) ? 'text-red-700' : 'text-gray-700'"></i>

      <!-- Label -->
      <span v-if="isOpen" class="text-sm truncate">
        {{ item.label }}
      </span>
    </a>
  </nav>

  <!-- Bottom (Profile Button) -->
  <div class="pt-2 border-t border-gray-300 relative" ref="profileMenuRef">
    <button @click="profileMenuOpen = !profileMenuOpen"
      class="w-full flex items-center gap-3 rounded-xl px-2.5 py-3 transition"
      :class="[profileMenuOpen ? 'bg-gray-100 ring-1 ring-gray-200' : 'hover:bg-gray-100', !isOpen ? 'has-tooltip sidebar-tooltip' : '']"
      :data-tooltip="!isOpen ? 'Profile' : ''">
      <!-- Avatar -->
      <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0 overflow-hidden">
        <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
        <span v-else class="text-blue-700 font-semibold text-sm" v-text="userInitials"></span>
      </div>

      <!-- Name and Role (visible when sidebar is open) -->
      <div v-if="isOpen" class="flex-1 text-left leading-tight min-w-0">
        <div class="text-sm font-semibold text-gray-900 dark-text-main truncate" v-text="userDisplayName"></div>
        <div class="text-xs text-gray-500 truncate" v-text="userEmail"></div>
      </div>

    </button>

    <!-- Dropdown Menu -->
    <div v-if="profileMenuOpen"
      class="absolute z-50 w-64 rounded-2xl bg-gray-800 text-white shadow-xl border border-white/10 overflow-hidden"
      :class="isOpen ? 'left-0 bottom-full mb-2' : 'left-full ml-2 bottom-0'">
      <!-- Header -->
      <div class="px-4 py-3 border-b border-white/10 overflow-hidden">
        <div class="text-sm font-semibold truncate" v-text="userDisplayName"></div>
        <div class="text-xs text-white/60 truncate"><?= htmlspecialchars(ucfirst($role)) ?></div>
      </div>

      <!-- Menu Items -->
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