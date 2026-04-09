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
        <span class="contact-name">{{ contact.name || contact.phone }}</span>
        <span class="contact-time">{{ contact.time }}</span>
      </div>
    </div>

    <div class="chat-panel">
      <div v-if="selectedContact" class="chat-messages">
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const search = ref('')
const contacts = ref([])
const selectedContact = ref(null)
const messages = ref([])

const filteredContacts = computed(() => {
  if (!search.value) return contacts.value
  const s = search.value.toLowerCase()
  return contacts.value.filter(c => 
    c.name?.toLowerCase().includes(s) || c.phone.includes(s)
  )
})

const selectContact = async (contact) => {
  selectedContact.value = contact
  try {
    const { data } = await axios.get(`/conversations/${contact.id}/messages`)
    messages.value = data.data
  } catch (e) {
    console.error(e)
  }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/conversations')
    contacts.value = data.data
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped>
.conversations-view { display: grid; grid-template-columns: 300px 1fr; height: 100vh; }
.conversations-list { border-right: 1px solid #e2e8f0; padding: 1rem; }
.search-input { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 1rem; }
.contact-item { padding: 1rem; cursor: pointer; border-radius: 4px; margin-bottom: 0.5rem; }
.contact-item:hover { background: #f1f5f9; }
.contact-item.active { background: #e0e7ff; }
.chat-panel { padding: 1rem; }
.chat-messages { display: flex; flex-direction: column; gap: 0.5rem; }
.message { max-width: 70%; padding: 0.75rem 1rem; border-radius: 12px; }
.message.in { align-self: flex-start; background: #e2e8f0; }
.message.out { align-self: flex-end; background: #0052cc; color: white; }
.empty-chat { display: flex; align-items: center; justify-content: center; height: 100%; color: #64748b; }
</style>
