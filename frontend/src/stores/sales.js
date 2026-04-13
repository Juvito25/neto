import { defineStore } from 'pinia'
import axios from 'axios'

export const useSalesStore = defineStore('sales', {
  state: () => ({
    pendingCount: 0,
    loading: false
  }),
  actions: {
    async fetchPendingCount() {
      this.loading = true
      try {
        const { data } = await axios.get('/sales?status=pending')
        this.pendingCount = data.total || data.data?.length || 0
      } catch (err) {
        console.error('Error fetching sales count:', err)
      } finally {
        this.loading = false
      }
    },
    decrementCount() {
      if (this.pendingCount > 0) {
        this.pendingCount--
      }
    },
    setCount(count) {
      this.pendingCount = count
    }
  }
})
