<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import Icon from '@/Components/Icons/Index.vue';
import axios from 'axios';

const props = defineProps({
    departmentId: {
        type: Number,
        required: true
    },
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'select']);

const articles = ref([]);
const searchQuery = ref('');
const loading = ref(false);

const filteredArticles = computed(() => {
    if (!searchQuery.value) return articles.value;
    const query = searchQuery.value.toLowerCase();
    return articles.value.filter(article => 
        article.title.toLowerCase().includes(query) ||
        article.content.toLowerCase().includes(query)
    );
});

async function fetchArticles() {
    loading.value = true;
    try {
        const response = await axios.get('/helpdesk/kb/articles', {
            params: {
                department_id: props.departmentId || undefined
            }
        });
        articles.value = response.data;
    } catch (error) {
        console.error('Failed to fetch KB articles:', error.response?.data || error);
    } finally {
        loading.value = false;
    }
}

function selectArticle(article) {
    emit('select', article);
    emit('close');
}

watch(() => props.departmentId, (newVal) => {
    fetchArticles();
}, { immediate: true });

onMounted(() => {
    if (props.departmentId) {
        fetchArticles();
    }
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-opacity-50 bg-blue-100 transition-opacity"  @click="$emit('close')"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-medium">Knowledge Base Articles</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
                        <Icon name="x" size="5" />
                    </button>
                </div>

                <!-- Search -->
                <div class="p-4 border-b">
                    <div class="relative">
                        <input v-model="searchQuery"
                               type="text"
                               placeholder="Search articles..."
                               class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        <Icon name="search" 
                              class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                              size="4" />
                    </div>
                </div>

                <!-- Articles List -->
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div v-if="loading" class="flex justify-center py-8">
                        <Icon name="loading" class="animate-spin text-gray-400" size="8" />
                    </div>

                    <div v-else-if="filteredArticles.length === 0" class="text-center py-8 text-gray-500">
                        No articles found
                    </div>

                    <div v-else class="space-y-4">
                        <button v-for="article in filteredArticles"
                                :key="article.id"
                                @click="selectArticle(article)"
                                class="w-full text-left p-4 rounded-lg border hover:bg-gray-50 transition-colors">
                            <h4 class="font-medium text-gray-900">{{ article.title }}</h4>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ article.content }}</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
