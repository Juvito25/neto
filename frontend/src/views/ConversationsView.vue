<template>
  <div class="conversations-view">
    <div class="conversations-list">
      <h2>Conversaciones</h2>
      <input v-model="search" placeholder="Buscar por nombre o teléfono" class="search-input" />
      <div 
        v-for="contact in filteredContacts" 
        :key="contact.id" 
        class="contact-item"
        :class="{ active: selectedContact?.id === contact.id }"
        @click="selectContact(contact)"
      >
        <div class="contact-info">
          <span class="contact-name">{{ contact.name || contact.phone }}</span>
        </div>
        <span class="contact-time">{{ contact.time }}</span>
      </div>
    </div>

    <div class="chat-panel">
      <div v-if="selectedContact" class="chat-messages" ref="chatMessagesRef" @scroll="onScroll">
        <div 
          v-for="msg in messages" 
          :key="msg.id" 
          class="message"
          :class="msg.direction"
        >
          {{ msg.body }}
        </div>
      </div>
      <div v-else class="empty-chat">
        Seleccioná una conversación
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'

const search = ref('')
const contacts = ref([])
const selectedContact = ref(null)
const messages = ref([])
const chatMessagesRef = ref(null)
let pollingInterval = null
const isAtBottom = ref(true)

const filteredContacts = computed(() => {
  if (!search.value) return contacts.value
  const s = search.value.toLowerCase()
  return contacts.value.filter(c => 
    c.name?.toLowerCase().includes(s) || c.phone.includes(s)
  )
})

const onScroll = () => {
  const el = chatMessagesRef.value
  if (!el) return
  
  // Consider "at bottom" if within 50px of the bottom
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
    messages.value = data.data.reverse() // Reverse to show oldest on top
    
    if (forceScroll || isAtBottom.value) {
      scrollToBottom()
    }
  } catch (e) {
    console.error(e)
  }
}

const selectContact = async (contact) => {
  selectedContact.value = contact
  isAtBottom.value = true // Reset to true to force scroll on new selection
  await loadMessages(contact.id, true)
}

const refreshContacts = async () => {
  try {
    const { data } = await axios.get('/conversations')
    contacts.value = data.data
  } catch (e) {
    console.error(e)
  }
}

import { useRoute } from 'vue-router'
const route = useRoute()

onMounted(async () => {
  await refreshContacts()
  
  if (route.query.selected_id && contacts.value.length > 0) {
    const foundContact = contacts.value.find(c => c.id == route.query.selected_id)
    if (foundContact) {
      await selectContact(foundContact)
    }
  }

  pollingInterval = setInterval(async () => {
    // Poll contacts
    await refreshContacts()
    
    // Poll messages if a contact is selected
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
.conversations-view { display: grid; grid-template-columns: 300px 1fr; height: 100vh; overflow: hidden; }
.conversations-list { border-right: 1px solid #e2e8f0; padding: 1rem; overflow-y: auto; }
.search-input { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 1rem; }
.contact-item { padding: 1rem; cursor: pointer; border-radius: 4px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
.contact-item:hover { background: #f1f5f9; }
.contact-item.active { background: #e0e7ff; }
.contact-info { display: flex; flex-direction: column; }
.contact-name { font-weight: 500; }
.contact-time { font-size: 0.75rem; color: #64748b; }
.chat-panel { padding: 1rem; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
.chat-messages { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; padding-right: 1rem; }
.message { max-width: 70%; padding: 0.75rem 1rem; border-radius: 12px; }
.message.in { align-self: flex-start; background: #e2e8f0; }
.message.out { align-self: flex-end; background: #0052cc; color: white; }
.empty-chat { display: flex; align-items: center; justify-content: center; height: 100%; color: #64748b; }
</style>
