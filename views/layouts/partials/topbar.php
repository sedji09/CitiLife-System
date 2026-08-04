<!-- Topbar -->
<div class="bg-white border-b px-6 py-4 relative <?= $isPatient ? 'hidden md:block' : '' ?>">
  <div class="flex items-center justify-between gap-4">
    <div>
      <?php if ($role === 'patient'): ?>
        <h1 class="text-xl font-semibold text-gray-800" v-text="userDisplayName"></h1>
        <p class="text-sm text-gray-500">Patient ID: <?= htmlspecialchars($userPatientNumber ?? '') ?></p>
      <?php else: ?>
        <h1 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($appName) ?></h1>
        <?php if ($role !== 'radiologist'): ?>
          <p class="text-sm text-gray-500"><?= $branchNameDisplay ?></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="flex items-center gap-3">
      <div
        class="hidden sm:flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-2 text-sm font-medium text-orange-800">
        <i data-lucide="calendar" class="w-4 h-4 text-orange-600"></i>
        <span id="topbarDateTime" class="whitespace-nowrap"></span>
      </div>

      <div class="relative" v-if="role !== 'patient'">
        <button @click.prevent="toggleChatMenu"
          class="relative rounded-full border border-gray-200 bg-white p-2 text-gray-700 hover:bg-gray-100 shadow-sm has-tooltip bottom-tooltip"
          :class="totalUnreadCount > 0 ? 'ring-2 ring-blue-200' : ''" type="button" aria-label="Messages"
          data-tooltip="Messages">
          <i data-lucide="message-square" class="w-5 h-5"></i>
          <span v-if="totalUnreadCount > 0"
            class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
            {{ totalUnreadCount > 99 ? '99+' : totalUnreadCount }}
          </span>
        </button>

        <!-- Chats Dropdown -->
        <div v-if="chatMenuOpen" ref="chatMenuRef"
          class="absolute right-0 top-full mt-2 z-50 w-80 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden flex flex-col"
          style="max-height: 480px;">

          <template v-if="!newMessageViewOpen">
            <div class="px-4 py-3 border-b flex justify-between items-center bg-white">
              <div class="text-xl font-bold text-gray-800">Chats</div>
              <div class="flex gap-2">
                <button @click="openNewMessageModal"
                  class="p-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition" title="New Message">
                  <i data-lucide="edit" class="w-4 h-4"></i>
                </button>
                <button @click="chatMenuOpen = false"
                  class="p-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition lg:hidden"
                  title="Close">
                  <i data-lucide="x" class="w-4 h-4"></i>
                </button>
              </div>
            </div>

            <div class="p-2 flex items-center gap-2">
              <!-- Back arrow when focused -->
              <button v-if="chatSearchFocused" @click="chatSearchFocused = false; chatSearchQuery = ''"
                class="p-1.5 rounded-full hover:bg-gray-100 text-gray-600 transition flex-shrink-0">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
              </button>
              <div
                class="flex-1 flex items-center bg-gray-100 rounded-full pl-4 pr-4 py-2 border border-transparent focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 text-gray-500 shrink-0 ml-0"></i>
                <input type="text" v-model="chatSearchQuery" @focus="onChatSearchFocus" @input="onChatSearchInput"
                  placeholder="Search Chats"
                  class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 ml-2 text-[15px] text-gray-800 p-0 m-0 w-full"
                  style="box-shadow: none;">
              </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar pb-2">

              <!-- Default Conversation List -->
              <template v-if="!chatSearchFocused">
                <template v-if="filteredConversations.length > 0">
                  <div v-for="conv in filteredConversations" :key="conv.id" @click="openChatWindow(conv)"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 cursor-pointer transition relative group mx-2 rounded-lg">
                    <div class="relative">
                      <div
                        class="h-12 w-12 rounded-full bg-blue-100 text-blue-700 font-semibold text-sm flex items-center justify-center overflow-hidden shrink-0">
                        <img v-if="conv.avatar" :src="conv.avatar" class="w-full h-full object-cover">
                        <span v-else>{{ conv.initials }}</span>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-semibold text-gray-900 truncate"
                        :class="conv.unread_count > 0 ? 'font-bold text-black' : ''">{{ conv.name }}</div>
                      <div class="text-xs truncate flex gap-1"
                        :class="conv.unread_count > 0 ? 'font-bold text-gray-900' : 'text-gray-500'">
                        <span v-if="conv.latest_message" class="truncate">
                          <span v-if="conv.sender_id === userId">You: </span>
                          {{ conv.latest_message }}
                        </span>
                        <span v-else-if="conv.latest_attachment" class="truncate italic">
                          <span v-if="conv.sender_id === userId">You </span>
                          {{ isImageAttachment(conv.latest_attachment) ? 'sent a photo' : 'sent a file' }}
                        </span>
                        <span>·</span>
                        <span>{{ formatTimeAgo(conv.latest_message_time) }}</span>
                      </div>
                    </div>
                    <div v-if="conv.unread_count > 0" class="w-3 h-3 bg-blue-500 rounded-full shrink-0"></div>
                  </div>
                </template>
                <div v-else class="py-8 text-center text-sm text-gray-500">
                  No conversations found.
                </div>
              </template>

              <!-- Search Mode List -->
              <template v-else>
                <template v-if="!chatSearchQuery">
                  <div v-if="recentSearches.length > 0" class="px-4 py-2 text-[13px] font-semibold text-gray-500">
                    Recent searches
                  </div>
                  <div v-for="(conv, index) in recentSearches" :key="'recent_' + conv.id" @click="openChatWindow(conv)"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 cursor-pointer transition relative group mx-2 rounded-lg">
                    <div
                      class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs flex items-center justify-center overflow-hidden shrink-0">
                      <img v-if="conv.avatar" :src="conv.avatar" class="w-full h-full object-cover">
                      <span v-else>{{ conv.initials }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[15px] text-gray-900 truncate">{{ conv.name }}</div>
                    </div>
                    <button @click.stop="removeRecentSearch(index)"
                      class="p-1.5 rounded-full hover:bg-gray-200 text-gray-400 transition" title="Remove">
                      <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                  </div>

                  <div class="px-4 py-2 text-[13px] font-semibold text-gray-500 mt-1">
                    Your contacts
                  </div>
                </template>

                <template v-if="filteredConversations.length > 0">
                  <div v-for="conv in filteredConversations" :key="'contact_' + conv.id" @click="openChatWindow(conv)"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 cursor-pointer transition relative group mx-2 rounded-lg">
                    <div
                      class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs flex items-center justify-center overflow-hidden shrink-0">
                      <img v-if="conv.avatar" :src="conv.avatar" class="w-full h-full object-cover">
                      <span v-else>{{ conv.initials }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[15px] text-gray-900 truncate">{{ conv.name }}</div>
                    </div>
                  </div>
                </template>

                <!-- Global Staff Search Results -->
                <template v-if="filteredStaffSearchResults?.length > 0">
                  <div class="px-4 py-2 text-[13px] font-semibold text-gray-500 mt-2 border-t border-gray-100 pt-3">
                    Other Staff Members
                  </div>
                  <div v-for="staff in filteredStaffSearchResults" :key="'global_' + staff.id"
                    @click="openChatWindow(staff)"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 cursor-pointer transition relative group mx-2 rounded-lg">
                    <div
                      class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs flex items-center justify-center overflow-hidden shrink-0">
                      <img v-if="staff.avatar" :src="staff.avatar" class="w-full h-full object-cover">
                      <span v-else>{{ staff.initials }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[15px] text-gray-900 truncate">{{ staff.name }}</div>
                      <div class="text-xs text-gray-500 truncate">{{ staff.role.charAt(0).toUpperCase() +
                        staff.role.slice(1) }}</div>
                    </div>
                  </div>
                </template>

                <div v-if="filteredConversations?.length === 0 && filteredStaffSearchResults?.length === 0"
                  class="py-8 text-center text-sm text-gray-500">
                  No users found.
                </div>
              </template>

            </div>
          </template>

          <template v-else>
            <div class="px-4 py-3 border-b flex justify-between items-center bg-white">
              <div class="flex items-center gap-2">
                <button @click="newMessageViewOpen = false; staffSearchQuery = ''; staffSearchResults = [];"
                  class="p-1.5 rounded-full hover:bg-gray-100 text-gray-600 transition flex-shrink-0" title="Back">
                  <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
                <div class="text-xl font-bold text-gray-800">New message</div>
              </div>
              <button @click="chatMenuOpen = false"
                class="p-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition lg:hidden"
                title="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
              </button>
            </div>
            
            <div class="p-3 border-b flex items-center gap-2 bg-white">
              <span class="text-sm font-semibold text-gray-600">To:</span>
              <input type="text" v-model="staffSearchQuery" @input="searchStaff" placeholder="Search staff by name or email"
                class="flex-1 focus:outline-none text-sm bg-transparent">
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
              <template v-if="isSearchingStaff">
                <div class="text-center py-4 text-sm text-gray-500">Searching...</div>
              </template>
              <template v-else-if="staffSearchResults.length > 0">
                <div v-for="staff in staffSearchResults" :key="staff.id" @click="startNewChat(staff)"
                  class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 cursor-pointer rounded-lg transition">
                  <div
                    class="h-10 w-10 rounded-full bg-blue-100 text-blue-700 font-semibold text-sm flex items-center justify-center shrink-0 overflow-hidden">
                    <img v-if="staff.avatar" :src="staff.avatar" class="w-full h-full object-cover">
                    <span v-else>{{ staff.initials }}</span>
                  </div>
                  <div>
                    <div class="text-sm font-bold text-gray-800">{{ staff.name }}</div>
                    <div class="text-xs text-gray-500">{{ staff.role }}</div>
                  </div>
                </div>
              </template>
              <div v-else class="text-center py-4 text-sm text-gray-400">No staff found.</div>
            </div>
          </template>
        </div>
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
          class="absolute right-2 top-full mt-2 z-50 w-80 rounded-xl border border-gray-200 bg-white shadow-xl">
          <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50 relative rounded-t-xl">
            <div class="text-sm font-bold text-gray-800">Notifications</div>
            
            <!-- Global Notification Options (3-dots) -->
            <button @click.stop="toggleGlobalNotificationOptions"
              class="global-notif-btn text-gray-400 hover:text-gray-600 transition p-1 rounded-full hover:bg-gray-200 relative z-10">
              <i data-lucide="more-horizontal" class="w-4 h-4"></i>
            </button>
            
            <!-- Global Options Dropdown (Facebook Style) -->
            <div v-show="globalNotificationOptionsOpen" style="display: none; right: 8px; top: 48px;" 
                 class="global-notif-dropdown absolute w-max z-[99]"
                 @click.stop>
              <!-- Smooth SVG Arrow -->
              <svg class="absolute text-white" style="top: -10px; right: 12px; width: 24px; height: 10px; filter: drop-shadow(0px -2px 2px rgba(0,0,0,0.06)); z-index: 20;" viewBox="0 0 24 10" fill="currentColor">
                <path d="M 0 10 C 8 10 10 0 12 0 C 14 0 16 10 24 10 Z"></path>
              </svg>
              
              <!-- Main Box -->
              <div class="relative rounded-xl p-1.5 z-10" style="background-color: white; box-shadow: 0 4px 16px rgba(0,0,0,0.12), 0 0 2px rgba(0,0,0,0.04);">
                <button @click.stop="markAllRead(); globalNotificationOptionsOpen = false;" class="w-full text-left px-3 pr-8 py-2 text-[15px] font-medium text-gray-800 flex items-center gap-3 transition-colors whitespace-nowrap rounded-lg" style="background-color: transparent;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='transparent'">
                  <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Mark all as read
                </button>
              </div>
            </div>
          </div>
          <div class="max-h-72 overflow-auto custom-scrollbar">
            <template v-if="notifications.length > 0">
              <div v-for="item in notifications" :key="item.id" @click="markAsRead(item.id, item.link)"
                class="relative flex items-center gap-3 px-4 py-3 hover:bg-gray-100 active:bg-gray-200 cursor-pointer border-b last:border-0 border-gray-100 transition-colors group"
                :class="item.is_read == 0 ? 'bg-blue-50/30' : ''">
                
                <div
                  class="h-10 w-10 rounded-full flex items-center justify-center shrink-0 transition-colors"
                  :class="item.is_read == 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400'">
                  <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
                <div class="text-sm flex-1 min-w-0 pr-2">
                  <div class="font-bold transition-colors truncate" :class="item.is_read == 0 ? 'text-gray-900' : 'text-gray-700'">{{ item.title }}
                  </div>
                  <div class="text-xs mt-0.5 leading-snug line-clamp-2" :class="item.is_read == 0 ? 'text-gray-800 font-medium' : 'text-gray-500'">{{ item.message }}</div>
                  <div class="text-[10px] mt-1 font-bold uppercase tracking-widest" :class="item.is_read == 0 ? 'text-blue-600' : 'text-gray-400'">{{ item.timeAgo }}</div>
                </div>
                
                <!-- Right side container for dot and 3-dots -->
                <div class="relative flex items-center justify-end shrink-0 w-8 h-8">
                  <!-- Unread dot -->
                  <div v-if="item.is_read == 0" class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                  
                  <!-- 3 Dots Menu Button -->
                  <button @click.stop="toggleNotificationOptions(item.id)" 
                          class="absolute inset-0 m-auto flex items-center justify-center w-8 h-8 rounded-full bg-transparent hover:bg-gray-200 text-gray-500 transition z-10">
                    <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                  </button>
                </div>
                
                <!-- Dropdown -->
                <div v-show="activeNotificationDropdown === item.id" 
                     class="absolute w-max bg-white border border-gray-200 rounded-xl shadow-lg z-[100] py-1.5 px-1.5 overflow-hidden"
                     style="display: none; right: 32px; top: 36px;"
                     @click.stop>
                  <button @click.stop="deleteNotification(item.id)" class="w-full text-left px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100 flex items-center gap-3 rounded-lg transition whitespace-nowrap">
                    <div class="bg-gray-100 p-1.5 rounded-full text-gray-700 shrink-0"><i data-lucide="x" class="w-4 h-4"></i></div>
                    Delete this notification
                  </button>
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
        </div>
      </div>

      <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
        <template v-if="userAvatar"><img :src="userAvatar" class="w-full h-full object-cover"></template>
        <span v-else class="text-blue-700 font-semibold text-sm" v-text="userInitials"></span>
      </div>
    </div>
  </div>
</div>