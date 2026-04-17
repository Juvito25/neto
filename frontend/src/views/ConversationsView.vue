<template>
  <div class="conversations-view-container">
    <!-- Contacts Sidebar -->
    <aside class="contacts-sidebar">
      <header class="sidebar-header">
        <h2 class="sidebar-title">Conversaciones</h2>
        <div class="search-container">
          <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input v-model="search" placeholder="Buscar..." class="search-input" />
        </div>
      </header>

      <div class="contacts-list">
        <div 
          v-for="contact in filteredContacts" 
          :key="contact.id" 
          class="contact-item"
          :class="{ active: selectedContact?.id === contact.id }"
          @click="selectContact(contact)"
        >
          <div class="contact-avatar" :style="{ backgroundColor: getAvatarColor(contact.phone) }">
            {{ getInitials(contact.name, contact.phone) }}
          </div>
          <div class="contact-content">
            <div class="contact-meta">
              <span class="contact-name">{{ contact.name || formatPhone(contact.phone) }}</span>
              <span class="contact-time">{{ contact.time }}</span>
            </div>
            <p class="contact-last-msg">{{ contact.last_message || 'Sin mensajes' }}</p>
          </div>
        </div>
        
        <div v-if="filteredContacts.length === 0" class="empty-contacts">
          <p>No se encontraron contactos</p>
        </div>
      </div>
    </aside>

    <!-- Chat Area -->
    <main class="chat-area">
      <div v-if="selectedContact" class="chat-container">
        <!-- Chat Header -->
        <header class="chat-header">
          <div class="header-contact-info">
            <div class="contact-avatar-sm" :style="{ backgroundColor: getAvatarColor(selectedContact.phone) }">
              {{ getInitials(selectedContact.name, selectedContact.phone) }}
            </div>
            <div class="header-text">
              <h3 class="header-name">{{ selectedContact.name || 'Desconocido' }}</h3>
              <p class="header-phone">{{ formatPhone(selectedContact.phone) }}</p>
            </div>
          </div>
          <div class="header-actions">
            <span class="bot-badge">
              <span class="badge-dot"></span>
              NETO AI Activo
            </span>
          </div>
        </header>

        <!-- Messages scroll area -->
        <div class="messages-viewport" ref="chatMessagesRef" @scroll="onScroll">
          <div class="messages-container">
            <div 
              v-for="(msg, index) in messages" 
              :key="msg.id || index" 
              class="message-row"
              :class="msg.direction"
            >
              <div class="message-bubble">
                <div class="message-text">{{ msg.body }}</div>
                <div class="message-metadata">
                  {{ formatTime(msg.created_at || msg.time) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat Footer -->
        <footer class="chat-footer">
          <div class="ai-status">
            <img src="/logos/neto-logo.svg" alt="NETO" class="neto-logo-mini" />
            <span>Respondido automáticamente por NETO AI</span>
          </div>
        </footer>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-chat-state">
        <EmptyState>
          <h3>Tus conversaciones aparecerán acá</h3>
          <p>Seleccioná un contacto de la izquierda para ver el historial y las respuestas automáticas.</p>
        </EmptyState>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import EmptyState from '@/components/EmptyState.vue'

const route = useRoute()
const search = ref('')
const contacts = ref([])
const selectedContact = ref(null)
const messages = ref([])
const chatMessagesRef = ref(null)
const isAtBottom = ref(true)
let pollingInterval = null

const filteredContacts = computed(() => {
  if (!search.value) return contacts.value
  const s = search.value.toLowerCase()
  return contacts.value.filter(c => 
    (c.name || '').toLowerCase().includes(s) || c.phone.includes(s)
  )
})

const getInitials = (name, phone) => {
  if (!name && !phone) return '?'
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

const getAvatarColor = (phone) => {
  if (!phone) return 'var(--color-primary)'
  const colors = ['#0052CC', '#008A45', '#D97706', '#DC2626', '#7C3AED', '#DB2777', '#0F172A']
  const hash = phone.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
  return colors[hash % colors.length]
}

const formatPhone = (phone) => {
  if (!phone) return ''
  return phone.replace(/(\+?\d{2})(\d{1})(\d{4})(\d{4})/, '$1 $2 $3-$4')
}

const formatTime = (time) => {
  if (!time) return ''
  // If time is just H:M
  if (time.length <= 5) return time
  // If full date
  const date = new Date(time)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const onScroll = () => {
  const el = chatMessagesRef.value
  if (!el) return
  const distanceToBottom = el.scrollHeight - el.scrollTop - el.clientHeight
  isAtBottom.value = distanceToBottom < 50
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatMessagesRef.value) {
    chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight
  }
}

const loadMessages = async (contactId, forceScroll = false) => {
  try {
    const { data } = await axios.get(`/conversations/${contactId}/messages`)
    // The API might return messages in different order, ensure oldest first for the viewport
    const newMessages = data.data.reverse()
    
    // Simple check to avoid flashing if no new messages
    if (JSON.stringify(newMessages) !== JSON.stringify(messages.value)) {
      messages.value = newMessages
      if (forceScroll || isAtBottom.value) {
        scrollToBottom()
      }
    }
  } catch (e) {
    console.error('Error loading messages:', e)
  }
}

const selectContact = async (contact) => {
  selectedContact.value = contact
  isAtBottom.value = true
  messages.value = [] // Clear current messages to show transition
  await loadMessages(contact.id, true)
}

const refreshContacts = async () => {
  try {
    const { data } = await axios.get('/conversations')
    contacts.value = data.data
  } catch (e) {
    console.error('Error refreshing contacts:', e)
  }
}

onMounted(async () => {
  await refreshContacts()
  
  if (route.query.selected_id && contacts.value.length > 0) {
    const foundContact = contacts.value.find(c => c.id == route.query.selected_id)
    if (foundContact) {
      await selectContact(foundContact)
    }
  }

  pollingInterval = setInterval(async () => {
    await refreshContacts()
    if (selectedContact.value) {
      await loadMessages(selectedContact.value.id, false)
    }
  }, 10000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
</script>

<style scoped>
.conversations-view-container {
  display: flex;
  height: calc(100vh - 40px); /* 40px for banner if exists, handled by flex usually */
  background: white;
}

/* Sidebar */
.contacts-sidebar {
  width: 320px;
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  background: white;
}

.sidebar-header {
  padding: 24px 20px;
  border-bottom: 1px solid var(--color-border);
}

.sidebar-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 16px;
  color: var(--color-dark);
}

.search-container {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: var(--color-text-muted);
}

.search-input {
  width: 100%;
  padding: 10px 12px 10px 36px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  transition: all var(--transition-fast);
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 3px var(--color-primary-10);
}

.contacts-list {
  flex: 1;
  overflow-y: auto;
}

.contact-item {
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  cursor: pointer;
  transition: background var(--transition-fast);
  border-bottom: 1px solid var(--color-surface);
}

.contact-item:hover {
  background: var(--color-surface);
}

.contact-item.active {
  background: var(--color-primary-10);
  border-left: 3px solid var(--color-primary);
  padding-left: 13px; /* 16 - 3 */
}

.contact-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.contact-content {
  flex: 1;
  min-width: 0;
}

.contact-meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 4px;
}

.contact-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.contact-time {
  font-size: 11px;
  color: var(--color-text-muted);
}

.contact-last-msg {
  font-size: 13px;
  color: var(--color-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.empty-contacts {
  padding: 40px 20px;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 14px;
}

/* Chat Area */
.chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--color-surface);
  position: relative;
  overflow: hidden;
}

.chat-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-header {
  height: 72px;
  background: white;
  border-bottom: 1px solid var(--color-border);
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.header-contact-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.contact-avatar-sm {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
  font-weight: 700;
}

.header-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-dark);
}

.header-phone {
  font-size: 12px;
  color: var(--color-text-muted);
}

.bot-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-success);
  background: var(--color-success-10);
  padding: 4px 10px;
  border-radius: var(--radius-full);
}

.badge-dot {
  width: 6px;
  height: 6px;
  background: var(--color-success);
  border-radius: 50%;
  animation: pulse-success 2s infinite;
}

/* Messages */
.messages-viewport {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.messages-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.message-row {
  display: flex;
  width: 100%;
}

.message-row.in {
  justify-content: flex-start;
}

.message-row.out {
  justify-content: flex-end;
}

.message-bubble {
  max-width: 65%;
  padding: 10px 14px;
  position: relative;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.in .message-bubble {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 4px 16px 16px 16px;
  color: var(--color-dark);
}

.out .message-bubble {
  background: var(--color-primary);
  border-radius: 16px 4px 16px 16px;
  color: white;
}

.message-text {
  font-size: 14px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.message-metadata {
  font-size: 10px;
  margin-top: 4px;
  display: flex;
  justify-content: flex-end;
  opacity: 0.6;
}

.out .message-metadata {
  color: white;
}

/* Chat Footer */
.chat-footer {
  padding: 12px 24px;
  background: white;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: center;
}

.ai-status {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  color: var(--color-text-muted);
}

.isotype-mini {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 16px;
}

.neto-logo-mini {
  height: 14px;
  width: auto;
}

.mini-bar {
  height: 3px;
  background: var(--color-primary);
  border-radius: 1px 3px 3px 1px;
}

.mini-bar:nth-child(2) { width: 80%; }
.mini-bar:nth-child(3) { width: 60%; }

/* Empty Chat State */
.empty-chat-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px;
}

.empty-chat-state h3 {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 12px;
}

.empty-chat-state p {
  color: var(--color-text-muted);
  max-width: 320px;
  font-size: 14px;
}

.isotype-bar {
  height: 12px;
  background: white;
  border: 2px solid var(--color-border);
  border-radius: 2px 12px 12px 2px;
}

.bar-1 { width: 100%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0s; }
.bar-2 { width: 80%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0.2s; }
.bar-3 { width: 60%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0.4s; }

@keyframes staggerBar {
  0% { transform: translateX(0); opacity: 0.5; }
  50% { transform: translateX(10px); opacity: 1; }
  100% { transform: translateX(0); opacity: 0.5; }
}

@keyframes pulse-success {
  0% { box-shadow: 0 0 0 0 rgba(0, 138, 69, 0.4); }
  70% { box-shadow: 0 0 0 6px rgba(0, 138, 69, 0); }
  100% { box-shadow: 0 0 0 0 rgba(0, 138, 69, 0); }
}
</style>
