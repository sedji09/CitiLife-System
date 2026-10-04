    <!-- FLOATING TOAST NOTIFICATIONS (System Alerts & Messages) -->
    <div id="toast-container"
      style="position: fixed; top: 80px; right: 24px; z-index: 10001; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: calc(100% - 48px); pointer-events: none;">
      
      <!-- ── Chat Message Toast Notifications ── -->
      <div v-for="mToast in messageToasts" :key="'msg_toast_' + mToast.id" @click="handleMessageToastClick(mToast)"
        style="pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; border-radius: 16px; padding: 12px 14px; box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.12), 0 8px 16px -6px rgba(0, 0, 0, 0.06); transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: 1px solid rgba(220, 38, 38, 0.18); background: #ffffff; position: relative; overflow: hidden;"
        class="toast-item group hover:shadow-2xl hover:scale-[1.02] dark:bg-zinc-900 dark:border-zinc-800">
        
        <!-- Left Red Indicator Accent -->
        <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(to bottom, #ef4444, #dc2626);"></div>

        <!-- Sender Profile Avatar (Clean, no message overlay icon) -->
        <div class="relative shrink-0" style="margin-left: 2px;">
          <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/70 text-red-700 dark:text-red-400 font-bold text-xs flex items-center justify-center overflow-hidden border border-red-200/80 dark:border-red-900/40 shadow-xs">
            <img v-if="mToast.avatar" :src="mToast.avatar" class="w-full h-full object-cover" @error="mToast.avatar = null">
            <span v-else>{{ mToast.initials }}</span>
          </div>
        </div>

        <!-- Sender Details & Message Body -->
        <div style="flex: 1; min-width: 0; padding-top: 1px;">
          <!-- Top Row: Sender Name + Role Badge (Subtle and Small) -->
          <div class="flex items-center justify-between gap-1.5 min-w-0">
            <div class="flex items-center gap-1.5 min-w-0 flex-1">
              <span class="text-[13px] font-bold text-gray-900 dark:text-zinc-100 truncate leading-tight">{{ mToast.name }}</span>
              <span v-if="mToast.role" class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-gray-400 dark:text-zinc-400 font-medium capitalize shrink-0 leading-none">
                {{ mToast.role.replace(/_/g, ' ') }}
              </span>
            </div>
          </div>

          <!-- Middle Row: Message Text Preview -->
          <div class="text-[11.5px] text-gray-600 dark:text-zinc-300 font-normal mt-0.5 leading-snug line-clamp-2 break-words">
            {{ getMessageToastText(mToast) }}
          </div>

          <!-- Bottom Row: Time and Date (Clean, subtle, no clock icon) -->
          <div class="text-[9.5px] text-gray-400 dark:text-zinc-500 font-normal mt-1 tracking-tight">
            {{ formatMessageToastDateTime(mToast.time) }}
          </div>
        </div>

        <!-- Dismiss Button -->
        <button @click.stop="dismissMessageToast(mToast.id)"
          style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; margin-top: -2px; margin-right: -4px;"
          class="toast-close opacity-50 hover:opacity-100 transition-opacity text-gray-400 hover:text-gray-700 dark:hover:text-zinc-200"
          title="Dismiss">
          <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- ── System Alert Toasts ── -->
      <div v-for="toast in toasts" :key="toast.id" @click="handleToastClick(toast)"
        style="pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; border-radius: 14px; padding: 14px 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;"
        :class="['toast-item group hover:shadow-xl hover:scale-[1.01]', 'toast-card-' + getNotificationCategory(toast)]">
        <!-- Icon matching notification section circle -->
        <div
          class="h-10 w-10 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-2xs"
          :class="getNotificationCircleClass(toast)">
          <i data-lucide="bell" class="w-5 h-5"></i>
        </div>
        <!-- Text details -->
        <div style="flex: 1; padding-top: 2px; min-width: 0;">
          <div style="font-size: 13px; font-weight: 700; line-height: 1.3;" class="toast-title truncate">{{ toast.title ||
            'Notification' }}</div>
          <div style="font-size: 11px; margin-top: 3px; line-height: 1.45; font-weight: 500;" class="toast-msg">{{
            toast.message }}</div>
        </div>
        <!-- Close button -->
        <button @click.stop="dismissToast(toast.id)"
          style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; margin-top: -2px; margin-right: -4px;"
          class="toast-close opacity-60 hover:opacity-100 transition-opacity">
          <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Undo Delete Toast -->
    <div v-if="showUndoToast" id="undo-toast-box" class="fixed z-[9999] px-4 py-3 rounded-xl shadow-2xl flex items-center justify-between gap-8" style="top: 24px; left: 50%; transform: translateX(-50%); background: #222222; color: #f4f4f5; min-width: 280px; transition: all 0.3s ease;">
      <div class="flex items-center gap-3">
        <!-- Circular Timer -->
        <div style="position: relative; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
          <svg width="22" height="22" viewBox="0 0 24 24" style="position: absolute; top: 0; left: 0; transform: rotate(-90deg);">
            <circle cx="12" cy="12" r="10" fill="none" stroke="#3f3f46" stroke-width="2.5"></circle>
            <circle cx="12" cy="12" r="10" fill="none" stroke="#ef4444" stroke-width="2.5"
                    stroke-dasharray="62.83"
                    stroke-linecap="round"
                    :style="{ strokeDashoffset: undoRingOffset + 'px', transition: 'stroke-dashoffset 5s linear' }"></circle>
          </svg>
          <!-- Timer text inside -->
          <span style="font-size: 12px; font-weight: 700; color: #ef4444; z-index: 1; line-height: 1;">{{ undoTimerCount }}</span>
        </div>
        <span style="font-size: 14px; font-weight: 600; color: #f4f4f5;">Deleted</span>
      </div>

      <button @click="undoDelete" class="hover:opacity-90 transition" style="background: #ef4444; color: #ffffff; font-weight: 700; font-size: 13px; padding: 6px 16px; border-radius: 99px; border: none; cursor: pointer;">
        Undo
      </button>
    </div>
