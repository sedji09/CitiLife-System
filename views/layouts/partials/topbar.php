      <!-- Topbar -->
      <div class="bg-white border-b px-6 py-4 relative <?= $isPatient ? 'hidden md:block' : '' ?>">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($appName) ?></h1>
            <?php if ($role !== 'radiologist'): ?>
              <p class="text-sm text-gray-500"><?= $branchNameDisplay ?></p>
            <?php endif; ?>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="hidden sm:flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-2 text-sm font-medium text-orange-800">
              <i data-lucide="calendar" class="w-4 h-4 text-orange-600"></i>
              <span id="topbarDateTime" class="whitespace-nowrap"></span>
            </div>

            <div class="relative">
              <button @click.prevent="toggleNotificationMenu"
                class="relative rounded-full border border-gray-200 bg-white p-2 text-gray-700 hover:bg-gray-100 shadow-sm has-tooltip bottom-tooltip"
                :class="notificationCount > 0 ? 'ring-2 ring-red-200' : ''" type="button" aria-label="Notifications"
                data-tooltip="Notifications">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span v-if="notificationCount > 0"
                  class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                  {{ notificationCount }}
                </span>
              </button>

              <div v-if="notificationMenuOpen" ref="notificationMenuRef"
                class="absolute right-2 top-full mt-2 z-50 w-80 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                  <div class="text-sm font-bold text-gray-800">Notifications</div>
                  <button @click="closeNotificationMenu"
                    class="text-[10px] items-center flex gap-1 font-bold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition">
                    <i data-lucide="x" class="w-3 h-3"></i> Close
                  </button>
                </div>
                <div class="max-h-72 overflow-auto custom-scrollbar">
                  <template v-if="notifications.length > 0">
                    <div v-for="item in notifications" :key="item.id" @click="markAsRead(item.id, item.link)"
                      class="flex gap-3 px-4 py-3 hover:bg-gray-100 active:bg-gray-200 cursor-pointer border-b last:border-0 border-gray-100 transition-colors group">
                      <div
                        class="h-8 w-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 transition-colors">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                      </div>
                      <div class="text-sm flex-1">
                        <div class="font-bold text-gray-900 transition-colors">{{ item.title }}
                        </div>
                        <div class="text-gray-600 text-xs mt-0.5 leading-snug line-clamp-2">{{ item.message }}</div>
                        <div class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-widest">{{ item.timeAgo
                          }}</div>
                      </div>
                    </div>
                  </template>
                  <div v-else class="py-12 flex flex-col items-center justify-center text-center px-6">
                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                      <i data-lucide="bell-off" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <div class="text-sm font-bold text-gray-800">No new notifications</div>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">We'll let you know when there's an update on
                      your cases or reports.</p>
                  </div>
                </div>
                <div v-if="notifications.length > 0" class="border-t px-4 py-3 bg-gray-50">
                  <button @click="markAllRead"
                    class="w-full rounded-md bg-red-600 text-white py-2 text-sm font-bold hover:bg-red-700 transition shadow-sm active:scale-[0.98]">
                    Mark all as read
                  </button>
                </div>
              </div>
            </div>

            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
              <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
              <span v-else class="text-blue-700 font-semibold text-sm" v-text="userInitials"></span>
            </div>
          </div>
        </div>
      </div>
