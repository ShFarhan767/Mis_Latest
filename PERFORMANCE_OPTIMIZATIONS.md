// Frontend Pagination Example for CustomerList.vue

// Before (loads ALL customers at once - SLOW):
const fetchCustomers = async () => {
const { data } = await axios.get('/api/customers');
customers.value = data.customers; // Could be thousands!
};

// After (loads 20 at a time - FAST):
const currentPage = ref(1);
const customers = ref([]);
const pagination = ref(null);

const fetchCustomers = async (page = 1) => {
try {
const { data } = await axios.get(`/api/customers?page=${page}&per_page=20`);
customers.value = data.customers;
pagination.value = data.pagination;
currentPage.value = page;
} catch (error) {
console.error('Error fetching customers:', error);
}
};

// Load more button or infinite scroll
const loadMore = () => {
if (pagination.value && currentPage.value < pagination.value.last_page) {
fetchCustomers(currentPage.value + 1);
}
};

// Template changes:
// Remove: {{ customers.length }} (all customers)
// Add: {{ pagination.total }} (with pagination)

// Add pagination controls:
/\*

<div class="pagination mt-4">
  <button 
    :disabled="currentPage === 1" 
    @click="fetchCustomers(currentPage - 1)">
    Previous
  </button>
  
  <span>Page {{ currentPage }} of {{ pagination.last_page }}</span>
  
  <button 
    :disabled="currentPage === pagination.last_page" 
    @click="fetchCustomers(currentPage + 1)">
    Next
  </button>
</div>
*/
