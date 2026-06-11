<!-- Global Chat Widget (Messenger Style) -->
<template v-if="role !== 'patient'">

  <!-- ── Visible Chat Windows (first 3) ──
       Shifted right: 68px to leave room for the bubble sidebar column -->
  <div v-for="(chat, chatIndex) in visibleChats.slice(0, 3)" :key="'win_' + chat.id"
    :style="{ position: 'fixed', bottom: '0', right: (68 + chatIndex * 336) + 'px', zIndex: 50, height: '420px', maxHeight: '80vh' }"
    class="w-80 bg-white shadow-2xl rounded-t-xl border border-gray-200 flex flex-col transition-all duration-200">
    <!-- Header -->
    <div
      class="flex items-center justify-between px-3 py-2 bg-blue-50 border-b border-blue-100 rounded-t-xl cursor-pointer select-none">
      <div class="flex items-center gap-2 overflow-hidden">
        <div
          class="h-8 w-8 rounded-full bg-blue-200 text-blue-700 font-semibold text-xs flex items-center justify-center shrink-0 overflow-hidden">
          <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover">
          <span v-else>{{ chat.initials }}</span>
        </div>
        <div class="text-sm font-bold text-gray-800 truncate">{{ chat.name }}</div>
      </div>
      <div class="flex items-center gap-0.5 text-blue-600">
        <button @click.stop="toggleChatMinimize(chat)" class="p-1 hover:bg-blue-100 rounded transition"
          title="Minimize">
          <i data-lucide="minus" class="w-4 h-4"></i>
        </button>
        <button @click.stop="closeChatWindow(chat)" class="p-1 hover:bg-blue-100 rounded transition" title="Close">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div v-show="!chat.minimized" class="flex-1 flex flex-col overflow-hidden bg-white">

      <!-- Loading -->
      <div v-if="chat.loading" class="flex-1 flex flex-col items-center justify-center gap-2">
        <div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
        <span class="text-xs text-gray-400">Loading...</span>
      </div>

      <!-- Messages area (always shows profile header at top, like Messenger) -->
      <div v-else class="flex-1 overflow-y-auto flex flex-col custom-scrollbar" :ref="'chatBody_' + chat.id">

        <!-- Spacer: pushes header + messages to the bottom when chat is empty -->
        <div class="flex-1"></div>

        <!-- Conversation header (avatar + name + lock note) — always visible at top -->
        <div class="flex flex-col items-center gap-2.5 text-center px-6 pt-6 pb-4">
          <div class="h-16 w-16 rounded-full bg-blue-100 text-blue-700 font-bold text-xl flex items-center justify-center overflow-hidden shadow-md border border-gray-200">
            <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover">
            <span v-else>{{ chat.initials }}</span>
          </div>
          <div>
            <div class="font-bold text-gray-900 text-sm leading-tight">{{ chat.name }}</div>
            <div class="text-xs text-gray-400 mt-0.5 capitalize">{{ chat.role ? chat.role.replace(/_/g, ' ') : '' }}</div>
          </div>
          <div class="flex items-start gap-1.5 text-xs text-gray-400 max-w-[210px]">
            <i data-lucide="lock" class="w-3 h-3 shrink-0 mt-0.5 text-gray-400"></i>
            <span class="leading-relaxed text-left">Messages are private and only visible to staff members in this chat.</span>
          </div>
        </div>

        <!-- Messages list -->
        <div class="flex flex-col gap-2 px-3 pb-3">
          <div v-for="msg in chat.messages" :key="msg.id" class="flex w-full"
            :class="msg.sender_id == userId ? 'justify-end' : 'justify-start'">
            <div class="flex flex-col max-w-[75%] gap-1" :class="msg.sender_id == userId ? 'items-end' : 'items-start'">

            <!-- Attachment Rendering -->
            <img v-if="msg.attachment && msg.attachment.match(/\.(jpeg|jpg|gif|png)$/i)"
              :src="'/<?= PROJECT_DIR ?>/' + msg.attachment"
              class="rounded-xl max-w-full cursor-pointer shadow-sm border border-black/5"
              style="max-height: 180px; object-fit: contain;">
            <a v-else-if="msg.attachment" :href="'/<?= PROJECT_DIR ?>/' + msg.attachment" target="_blank"
              class="flex items-center gap-2 bg-gray-200 text-gray-800 px-3 py-2 rounded-xl text-sm hover:bg-gray-300 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                <path d="M14 3v5h5M16 13H8M16 17H8M10 9H8" />
              </svg>
              View File
            </a>

            <!-- Text Message -->
            <div v-if="msg.message" class="rounded-2xl px-3 py-1.5 text-[15px] break-words leading-snug"
              :class="msg.sender_id == userId ? 'bg-blue-500 text-white rounded-br-[4px]' : 'bg-gray-100 text-gray-800 rounded-bl-[4px]'">
              {{ msg.message }}
            </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Attachment Preview Area -->
      <div v-if="chat.attachmentPreview"
        class="px-3 pt-2 pb-1 border-t border-gray-100 bg-white flex items-center relative shrink-0">
        <div class="relative inline-block">
          <img :src="chat.attachmentPreview" class="h-16 w-16 object-cover rounded-lg border border-gray-200 shadow-sm">
          <button @click="removeChatAttachment(chat)"
            class="absolute -top-1.5 -right-1.5 bg-gray-800 hover:bg-black text-white rounded-full p-0.5 shadow-md border border-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>

      <!-- Input -->
      <div class="p-2 border-t border-gray-100 flex items-center gap-1.5 shrink-0 bg-white">
        <!-- Attachment Button -->
        <div class="relative group">
          <button @click="triggerChatAttachment(chat.id)"
            class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-full transition flex-shrink-0" :disabled="chat.sending">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"
              stroke="none">
              <path
                d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
            </svg>
          </button>
          <div
            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-3 py-1.5 text-white text-[13px] font-medium rounded-[8px] shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50"
            style="background-color: #333333;">Attach a file up to 25 MB</div>
        </div>
        <input type="file" :ref="'chatAttachment_' + chat.id" class="hidden" accept="image/*,.pdf,.doc,.docx"
          @change="handleChatAttachment(chat, $event)">

        <input type="text" v-model="chat.newMessage" @keyup.enter="sendMessage(chat)" placeholder="Aa"
          class="flex-1 bg-gray-100 rounded-full px-3 py-1.5 text-[15px] focus:outline-none border-0"
          style="pointer-events: auto !important; position: relative; z-index: 51;">
        <button @click="sendMessage(chat)" class="text-blue-500 hover:text-blue-700 p-1 transition flex-shrink-0"
          :disabled="(!chat.newMessage && !chat.selectedAttachment) || chat.sending">
          <i data-lucide="send" class="w-5 h-5"
            :class="(chat.newMessage || chat.selectedAttachment) ? '' : 'opacity-50'"></i>
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
            <span>{{ chat.messages[chat.messages.length - 1].sender_id == userId ? 'You: ' : '' }}</span>{{
            chat.messages[chat.messages.length - 1].message }}
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
        class="w-full h-full rounded-full bg-blue-200 text-blue-700 font-bold text-lg flex items-center justify-center transition-transform duration-150 hover:scale-105 shadow-xl border border-gray-200 cursor-pointer overflow-hidden">
        <img v-if="chat.avatar" :src="chat.avatar" class="w-full h-full object-cover">
        <span v-else>{{ chat.initials }}</span>
      </div>

      <!-- Unread Badge -->
      <div v-if="chat.unreadCount > 0"
        class="absolute top-0 right-0 h-3.5 w-3.5 bg-green-500 rounded-full border-2 border-white pointer-events-none">
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
    <div v-if="bubbleChats.length > 5"
      class="relative flex items-center justify-end"
      style="width: 56px; height: 56px;">

      <!-- Toggle button (separate from dropdown so clicks don't conflict) -->
      <div class="relative cursor-pointer w-full h-full" @click.stop="isGroupMenuOpen = !isGroupMenuOpen">
        <div class="w-full h-full rounded-full overflow-hidden shadow-xl border border-gray-200 relative flex items-center justify-center transition-transform duration-150 hover:scale-105"
          :class="isGroupMenuOpen ? 'ring-[3px] ring-gray-600 ring-offset-2' : ''">
          <img v-if="bubbleChats[5].avatar" :src="bubbleChats[5].avatar" class="w-full h-full object-cover">
          <div v-else class="w-full h-full bg-gray-700 text-white font-bold text-lg flex items-center justify-center">{{ bubbleChats[5].initials }}</div>
          <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
            <span class="text-white font-bold text-lg">+{{ bubbleChats.length - 5 }}</span>
          </div>
        </div>
      </div>

      <!-- Dropdown — OUTSIDE the toggle div so clicks don't bubble to toggle -->
      <div v-show="isGroupMenuOpen"
        class="absolute flex items-center"
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
        <div style="width: 0; height: 0; border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-left: 6px solid white;"></div>
      </div>
    </div>

  </div>

</template>

<!-- ── New Message Modal ── -->
<div v-if="newMessageModalOpen && role !== 'patient'"
  class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm flex flex-col overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50">
      <div class="font-bold text-gray-800">New message</div>
      <button @click="newMessageModalOpen = false" class="text-gray-400 hover:text-gray-700">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
    <div class="p-3 border-b flex items-center gap-2">
      <span class="text-sm font-semibold text-gray-600">To:</span>
      <input type="text" v-model="staffSearchQuery" @input="searchStaff" placeholder="Search staff by name or email"
        class="flex-1 focus:outline-none text-sm bg-transparent">
    </div>
    <div class="flex-1 overflow-y-auto max-h-80 custom-scrollbar p-2">
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
  </div>
</div>