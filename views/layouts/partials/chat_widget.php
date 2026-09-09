<!-- Global Chat Widget (Messenger Style) -->
<div id="chat-widget-wrapper">
  <template v-if="role !== 'patient'">

    <!-- ── Visible Chat Windows (first 3) ──
       Shifted right: 80px to leave room for the bubble sidebar column, 12px gap between windows -->
    <div v-for="(chat, chatIndex) in visibleChats.slice(0, 3)" :key="'win_' + chat.id"
      :style="{ position: 'fixed', bottom: '0', right: (80 + chatIndex * 342) + 'px', width: '330px', zIndex: 50, height: '455px', maxHeight: '85vh' }"
      class="bg-white shadow-2xl rounded-t-2xl border border-gray-200/90 flex flex-col transition-all duration-200 overflow-hidden font-sans">
      
      <!-- Header (FB Messenger Style) -->
      <div
        class="flex items-center justify-between px-3 py-2 bg-white border-b border-gray-100 rounded-t-2xl select-none z-10 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
        <!-- Contact Info -->
        <div class="flex items-center gap-2 min-w-0 flex-1 cursor-pointer" @click="toggleChatMinimize(chat)">
          <div class="relative shrink-0">
            <div
              class="h-9 w-9 rounded-full bg-red-100 text-red-700 font-semibold text-xs flex items-center justify-center overflow-hidden">
              <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover" @error="chat.avatar = null">
              <span v-else>{{ chat.initials }}</span>
            </div>
          </div>
          <div class="flex flex-col min-w-0">
            <span class="text-[13.5px] font-bold text-gray-900 truncate leading-tight">{{ chat.name }}</span>
            <div class="text-[11px] text-gray-500 truncate leading-tight capitalize mt-0.5 flex items-center gap-1">
              <span>{{ chat.role ? chat.role.replace(/_/g, ' ') : 'Active now' }}</span>
            </div>
          </div>
        </div>

        <!-- Right Header Action Icons (Minimize, Close) -->
        <div class="flex items-center gap-0.5 text-gray-500">
          <button @click.stop="toggleChatMinimize(chat)" class="p-1.5 hover:bg-gray-100 rounded-full hover:text-gray-800 transition" title="Minimize">
            <i data-lucide="minus" class="w-4 h-4"></i>
          </button>
          <button @click.stop="closeChatWindow(chat)" class="p-1.5 hover:bg-red-50 rounded-full hover:text-red-600 transition" title="Close">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div v-show="!chat.minimized" class="flex-1 flex flex-col overflow-hidden bg-white">

        <!-- Loading -->
        <div v-if="chat.loading" class="flex-1 flex flex-col items-center justify-center gap-2">
          <div class="w-6 h-6 border-2 border-red-400 border-t-transparent rounded-full animate-spin"></div>
          <span class="text-xs text-gray-400">Loading messages...</span>
        </div>

        <!-- Messages Area -->
        <div v-else class="flex-1 overflow-y-auto overflow-x-hidden flex flex-col custom-scrollbar pb-2" :ref="'chatBody_' + chat.id" style="overflow-x: hidden !important;">

          <!-- Conversation Intro (avatar + name + role) -->
          <div class="flex flex-col items-center gap-2 text-center px-6 pt-7 pb-4 shrink-0">
            <div
              class="h-14 w-14 rounded-full bg-red-100 text-red-700 font-bold text-lg flex items-center justify-center overflow-hidden shadow-sm border border-gray-100">
              <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover" @error="chat.avatar = null">
              <span v-else>{{ chat.initials }}</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="font-bold text-gray-900 text-[14px] leading-tight">{{ chat.name }}</div>
              <div class="text-[11px] text-gray-500 mt-1 px-2.5 py-0.5 bg-gray-100 rounded-full font-medium capitalize">
                {{ chat.role ? chat.role.replace(/_/g, ' ') : 'Staff Member' }}
              </div>
              <div class="text-[11px] text-gray-400 mt-1.5">
                You're connected on CitiLife Chat
              </div>
            </div>
          </div>

          <!-- Messages list (FB Messenger Style) -->
          <div class="flex flex-col px-3 pb-2 mt-auto">
            <div v-for="(msg, msgIndex) in chat.messages" :key="msg.id" class="flex flex-col w-full min-w-0"
              :style="(msgIndex > 0 && chat.messages[msgIndex - 1].sender_id !== msg.sender_id) ? 'margin-top: 16px;' : 'margin-top: 2px;'">

              <!-- Message Row -->
              <div class="flex w-full min-w-0 group relative"
                :class="msg.sender_id == userId ? 'justify-end' : 'justify-start items-end gap-1.5'">

                <!-- Incoming Message Avatar (pinned beside the last message of the cluster) -->
                <template v-if="msg.sender_id != userId">
                  <div v-if="msgIndex === chat.messages.length - 1 || chat.messages[msgIndex + 1].sender_id != msg.sender_id"
                    class="w-7 h-7 rounded-full bg-red-100 text-red-700 font-semibold text-[10px] flex items-center justify-center shrink-0 overflow-hidden shadow-xs border border-gray-100 mb-0.5">
                    <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover" @error="chat.avatar = null">
                    <span v-else>{{ chat.initials }}</span>
                  </div>
                  <div v-else class="w-7 shrink-0"></div>
                </template>

                <!-- Main Content (Attachments + Bubble + Seen) -->
                <div class="flex flex-col max-w-[85%] min-w-0"
                  :class="msg.sender_id == userId ? 'items-end' : 'items-start'">

                  <!-- Sender Name (perfectly aligned with the chat bubble) -->
                  <div v-if="msg.sender_id != userId && (msgIndex === 0 || chat.messages[msgIndex - 1].sender_id != msg.sender_id)"
                    class="text-[11px] text-gray-500 font-semibold mb-1 select-none leading-none">
                    {{ chat.name }}
                  </div>

                  <!-- Attachment Rendering -->
                  <!-- Image Attachment -->
                  <div v-if="msg.attachment && isImageAttachment(msg.attachment)"
                    class="relative group/att overflow-hidden rounded-2xl shadow-xs border border-black/10 my-0.5"
                    style="max-width: 220px;">
                    <img :src="formatAttachmentUrl(msg.attachment)"
                      class="block w-full cursor-pointer hover:opacity-95 transition-all duration-200"
                      style="max-height: 250px; object-fit: cover;"
                      @click="openLightbox(chat, msg)"
                      @load="scrollToBottom(chat)">
                  </div>

                  <!-- FB Messenger Style File Attachment Card -->
                  <a v-else-if="msg.attachment"
                    :href="formatAttachmentUrl(msg.attachment)"
                    :download="getAttachmentFileName(msg.attachment)"
                    target="_blank"
                    class="flex items-center gap-2.5 p-2.5 my-0.5 rounded-2xl transition-all duration-150 border shadow-xs group/file select-none hover:brightness-95"
                    :style="(msg.sender_id == userId ? 'background-color: #b91c1c; color: #ffffff; border-color: #b91c1c;' : 'background-color: #f0f2f5; color: #111827; border-color: #e5e7eb;') + ' max-width: 220px; text-decoration: none;'"
                    :title="'Download ' + getAttachmentFileName(msg.attachment)">
                    <!-- Document Icon -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                      :style="msg.sender_id == userId ? 'background-color: rgba(255, 255, 255, 0.2); color: #ffffff;' : 'background-color: #ffffff; color: #374151; border: 1px solid #e5e7eb;'">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                      </svg>
                    </div>

                    <!-- File Info -->
                    <div class="flex-1 min-w-0 flex flex-col text-left">
                      <span class="text-xs font-semibold truncate leading-tight"
                        :style="msg.sender_id == userId ? 'color: #ffffff;' : 'color: #111827;'">
                        {{ getAttachmentFileName(msg.attachment) }}
                      </span>
                      <span class="text-[10px] mt-0.5 uppercase tracking-wider font-semibold"
                        :style="msg.sender_id == userId ? 'color: rgba(255, 255, 255, 0.85);' : 'color: #6b7280;'">
                        {{ getAttachmentExt(msg.attachment) }}
                      </span>
                    </div>

                    <!-- Download Arrow -->
                    <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-transform group-hover/file:translate-y-0.5"
                      :style="msg.sender_id == userId ? 'background-color: rgba(255, 255, 255, 0.2); color: #ffffff;' : 'background-color: #e5e7eb; color: #4b5563;'">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                      </svg>
                    </div>
                  </a>

                  <!-- Text Message Bubble (font: 13px, limit ~22 characters/spaces per line before auto-wrap) -->
                  <div class="relative group/bubble w-fit">
                    <div v-if="msg.message"
                      class="px-3 py-1.5 text-[13px] leading-[18px] select-text shadow-xs transition-all w-fit"
                      :style="(msg.sender_id != userId ? 'background-color: #f0f2f5; color: #050505;' : 'background-color: #dc2626; color: #ffffff;') + ' max-width: 205px; overflow-wrap: anywhere; word-break: normal; ' + getBubbleRadius(chat, msg, msgIndex)"
                      :class="msg.sender_id == userId ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-900'">
                      {{ msg.message }}
                    </div>

                  </div>

                  <!-- Seen indicator: Miniature recipient avatar for Seen or 'Sent' text -->
                  <div v-if="msg.sender_id == userId && msgIndex === chat.messages.length - 1"
                    class="flex items-center gap-1 mt-0.5 mr-0.5">
                    <div v-if="msg.is_read == 1" class="w-3.5 h-3.5 rounded-full overflow-hidden border border-white shadow-xs" title="Seen">
                      <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover" @error="chat.avatar = null">
                      <div v-else class="w-full h-full bg-red-100 text-red-700 text-[8px] flex items-center justify-center font-bold">{{ chat.initials }}</div>
                    </div>
                    <span v-else class="text-[11px] text-gray-400 font-medium">Sent</span>
                  </div>

                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Attachment Preview Area -->
        <div v-if="chat.attachmentPreviews && chat.attachmentPreviews.length > 0"
          class="px-3 pt-2 pb-1 border-t border-gray-100 bg-white flex flex-wrap items-center gap-2 relative shrink-0">
          <div v-for="(preview, idx) in chat.attachmentPreviews" :key="idx" class="relative inline-block">
            <img v-if="preview.isImage" :src="preview.url"
              class="h-16 w-16 object-cover rounded-lg border border-gray-200 shadow-sm">
            <div v-else
              class="h-16 w-16 bg-gray-100 rounded-lg border border-gray-200 shadow-sm flex flex-col items-center justify-center text-[10px] text-gray-500 overflow-hidden px-1 text-center"
              :title="preview.name">
              <i data-lucide="file" class="w-6 h-6 mb-1 text-gray-400"></i>
              <span class="truncate w-full">{{ preview.name }}</span>
            </div>
            <button @click="removeChatAttachment(chat, idx)"
              class="absolute bg-gray-800 hover:bg-black text-white rounded-full p-0.5 shadow-md border border-white z-10"
              style="top: 4px; right: 4px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>

        <!-- Input (FB Messenger Desktop Style) -->
        <div class="px-2 py-2 border-t border-gray-100 flex items-center gap-1.5 shrink-0 bg-white">
          <!-- Photo icon -->
          <button type="button" @click="triggerChatAttachment(chat.id)"
            class="w-8 h-8 rounded-full text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0"
            title="Attach photo">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
            </svg>
          </button>

          <input type="file" :ref="'chatAttachment_' + chat.id" class="hidden" accept="image/*,.pdf,.doc,.docx" multiple
            @change="handleChatAttachment(chat, $event)">

          <!-- Capsule input (with subtle border and soft gray background) -->
          <div class="flex-1 relative flex items-center">
            <input type="text" v-model="chat.newMessage" @keyup.enter="sendMessage(chat)" placeholder="Aa"
              class="w-full focus:bg-white rounded-full px-3.5 py-1.5 text-[14px] text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-red-400 transition-all"
              style="background-color: #f0f2f5; border: 1px solid #d1d5db; pointer-events: auto !important; position: relative; z-index: 51;">
          </div>

          <!-- Dynamic Thumbs Up / Send Button -->
          <button v-if="chat.newMessage && chat.newMessage.trim() || (chat.selectedAttachments && chat.selectedAttachments.length > 0)"
            @click="sendMessage(chat)" 
            class="w-8 h-8 rounded-full text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0"
            title="Send">
            <i data-lucide="send" class="w-4 h-4"></i>
          </button>
          <button v-else
            type="button" @click="chat.newMessage = '👍'; sendMessage(chat)"
            class="w-8 h-8 rounded-full text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0"
            title="Send a Like">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M2 20h2c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1H2v11zm19.83-7.12c.11-.25.17-.52.17-.8V11c0-1.1-.9-2-2-2h-5.5l.92-4.65c.05-.22.02-.46-.08-.66-.23-.45-.77-.7-1.28-.56L10.5 4.3 6.8 8.01C6.29 8.52 6 9.22 6 9.94V19c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-.12z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Unified Chat Bubbles (Minimized + Overflow) ── -->
    <div class="flex"
      style="position: fixed; bottom: 16px; right: 16px; z-index: 60; flex-direction: column-reverse; gap: 12px;">

      <!-- Individual Bubbles (Max 5) -->
      <div v-for="(chat, bubbleIndex) in bubbleChats.slice(0, 5)" :key="'bubble_' + chat.id"
        class="relative flex items-center justify-end group" style="width: 56px; height: 56px;">
        <!-- Name Tooltip -->
        <div
          class="absolute pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 z-50 flex items-center"
          style="right: 64px; top: 50%; transform: translateY(-50%); filter: drop-shadow(0 2px 8px rgba(0,0,0,0.12));">
          <div class="bg-white rounded-xl px-3 py-1.5 flex flex-col justify-center whitespace-nowrap">
            <div class="font-bold text-gray-900 text-sm leading-tight">{{ chat.name }}</div>
            <div v-if="chat.messages && chat.messages.length > 0" class="text-gray-500 truncate mt-0.5 leading-snug"
              style="font-size: 13px;">
              <span>{{ chat.messages[chat.messages.length - 1].sender_id == userId ? 'You: ' : '' }}</span>
              <span v-if="chat.messages[chat.messages.length - 1].message">{{ chat.messages[chat.messages.length - 1].message }}</span>
              <span v-else-if="chat.messages[chat.messages.length - 1].attachment" class="italic">
                {{ isImageAttachment(chat.messages[chat.messages.length - 1].attachment) ? 'sent a photo' : 'sent a file' }}
              </span>
            </div>
            <div v-else class="text-gray-400 italic mt-0.5 leading-snug" style="font-size: 13px;">No messages yet</div>
          </div>
          <!-- Tooltip arrow pointing right -->
          <div
            style="width: 0; height: 0; border-top: 5px solid transparent; border-bottom: 5px solid transparent; border-left: 6px solid white; margin-left: -1px;">
          </div>
        </div>

        <!-- Bubble -->
        <div @click="bringChatToFront(chat)"
          class="w-full h-full rounded-full bg-red-100 text-red-700 font-bold text-lg flex items-center justify-center transition-transform duration-150 hover:scale-105 shadow-xl border border-gray-200 cursor-pointer overflow-hidden">
          <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover" @error="chat.avatar = null">
          <span v-else>{{ chat.initials }}</span>
        </div>

        <!-- Unread Badge (shows count number like Messenger) -->
        <div v-if="chat.unreadCount > 0"
          class="absolute -top-1 -right-1 min-w-[20px] h-5 bg-red-500 text-white text-[11px] font-bold rounded-full border-2 border-white flex items-center justify-center px-1 pointer-events-none z-20">
          {{ chat.unreadCount > 99 ? '99+' : chat.unreadCount }}
        </div>

        <!-- Online indicator dot -->
        <div class="absolute bg-green-500 rounded-full pointer-events-none"
          style="bottom: 2px; right: 2px; width: 14px; height: 14px; border: 2px solid white;"></div>

        <!-- Close ×  (appears top-right on hover) -->
        <button @click.stop="closeChatWindow(chat)"
          class="absolute bg-white border border-gray-200 text-black rounded-full shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-150 flex items-center justify-center hover:bg-gray-100 z-10"
          style="top: -4px; right: -4px; width: 22px; height: 22px;" title="Close Chat">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Grouped '+N' Bubble — only shows when more than 5 bubbles exist -->
      <div v-if="bubbleChats.length > 5" class="relative flex items-center justify-end"
        style="width: 56px; height: 56px;">

        <!-- Toggle button (separate from dropdown so clicks don't conflict) -->
        <div class="relative cursor-pointer w-full h-full" @click.stop="isGroupMenuOpen = !isGroupMenuOpen">
          <div
            class="w-full h-full rounded-full overflow-hidden shadow-xl border border-gray-200 relative flex items-center justify-center transition-transform duration-150 hover:scale-105"
            :class="isGroupMenuOpen ? 'ring-[3px] ring-red-600 ring-offset-2' : ''">
            <img v-if="bubbleChats[5].avatar" :src="bubbleChats[5].avatar" class="w-full h-full object-cover" @error="bubbleChats[5].avatar = null">
            <div v-else class="w-full h-full bg-gray-700 text-white font-bold text-lg flex items-center justify-center">
              {{
              bubbleChats[5].initials }}</div>
            <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
              <span class="text-white font-bold text-lg">+{{ bubbleChats.length - 5 }}</span>
            </div>
          </div>
        </div>

        <!-- Unread badge on +N group button -->
        <div v-if="bubbleChats.slice(5).some(c => c.unreadCount > 0)"
          class="absolute -top-1 -right-1 min-w-[20px] h-5 bg-red-500 text-white text-[11px] font-bold rounded-full border-2 border-white flex items-center justify-center px-1 pointer-events-none z-20">
          {{ bubbleChats.slice(5).reduce((sum, c) => sum + (c.unreadCount || 0), 0) > 99 ? '99+' : bubbleChats.slice(5).reduce((sum, c) => sum + (c.unreadCount || 0), 0) }}
        </div>
        <div v-show="isGroupMenuOpen" class="absolute flex items-center"
          style="right: 64px; top: 50%; transform: translateY(-50%); z-index: 9999; filter: drop-shadow(0 4px 16px rgba(0,0,0,0.18));"
          @click.stop>
          <div class="bg-white rounded-xl p-2 flex flex-col shadow-lg" style="min-width: 240px; max-width: 300px;">
            <template v-for="groupedChat in bubbleChats.slice(5)" :key="'grouped_' + groupedChat.id">
              <div
                class="flex items-center justify-between px-3 py-2.5 hover:bg-gray-100 rounded-lg cursor-pointer transition-colors mb-0.5 last:mb-0 select-none"
                @mousedown.stop="bringChatToFront(groupedChat)">
                <div class="font-medium text-black text-[15px] truncate mr-4 tracking-wide">{{ groupedChat.name }}</div>
                <button @mousedown.stop="closeChatWindow(groupedChat)"
                  class="text-gray-500 hover:text-gray-800 transition-colors p-1 -mr-1 bg-transparent border-0 rounded-full hover:bg-gray-200 flex-shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
            </template>
          </div>
          <!-- Tooltip arrow pointing right -->
          <div
            style="width: 0; height: 0; border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-left: 6px solid white;">
          </div>
        </div>
      </div>

    </div>

  </template>


  <!-- ── Image Lightbox Gallery ── -->
  <div v-if="lightboxOpen && lightboxImages.length > 0"
    class="fixed inset-0 z-[9999] flex flex-col items-center justify-center select-none"
    style="background-color: rgba(30, 30, 30, 0.98); display: none;"
    :style="{ display: (lightboxOpen && lightboxImages.length > 0) ? 'flex' : 'none', backgroundColor: 'rgba(30, 30, 30, 0.98)' }">

    <!-- Top Right Actions -->
    <div class="absolute top-4 right-4 flex items-center gap-4 z-10">
      <!-- Download Button -->
      <a :href="lightboxImages[lightboxIndex]" download target="_blank"
        class="text-white hover:opacity-100 opacity-80 transition p-2 bg-black/40 hover:bg-black/60 rounded-full"
        title="Download">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
      </a>
      <!-- Close Button -->
      <button @click.stop="lightboxOpen = false"
        class="text-white opacity-80 hover:opacity-100 transition p-2 bg-black/40 hover:bg-black/60 rounded-full"
        title="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Main Image Container -->
    <div class="flex-1 w-full flex items-center justify-center relative overflow-hidden px-16 py-8">
      <!-- Prev Button -->
      <button v-if="lightboxImages.length > 1" @click.stop="prevLightboxImage()"
        class="absolute left-4 text-white opacity-60 hover:opacity-100 transition p-3 bg-black/20 hover:bg-black/50 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>

      <!-- Image -->
      <img :src="lightboxImages[lightboxIndex]"
        class="max-w-full max-h-full object-contain drop-shadow-2xl transition-transform duration-200">

      <!-- Next Button -->
      <button v-if="lightboxImages.length > 1" @click.stop="nextLightboxImage()"
        class="absolute right-4 text-white opacity-60 hover:opacity-100 transition p-3 bg-black/20 hover:bg-black/50 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

    <!-- Thumbnails Row -->
    <div v-if="lightboxImages.length > 1"
      class="h-24 w-full bg-black/40 flex items-center justify-center gap-2 px-4 py-2 overflow-x-auto flex-shrink-0"
      style="scrollbar-width: thin;">
      <img v-for="(img, idx) in lightboxImages" :key="'thumb_' + idx" :src="img" @click.stop="lightboxIndex = idx"
        class="h-16 w-16 object-cover rounded cursor-pointer transition-all duration-200 flex-shrink-0"
        :class="idx === lightboxIndex ? 'ring-2 ring-white opacity-100 scale-105' : 'opacity-50 hover:opacity-80'">
    </div>
  </div>
</div>