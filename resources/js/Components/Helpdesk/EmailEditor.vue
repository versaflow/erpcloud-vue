<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Icon from '@/Components/Icons/Index.vue';

const props = defineProps({
    recipient: {
        type: String,
        required: true
    },
    ccList: {
        type: Array,
        default: () => []
    },
    disabled: {
        type: Boolean,
        default: false
    },
    subject: {
        type: String,
        default: ''
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['send']);

const showCc = ref(false);
const showBcc = ref(false);
const cc = ref('');
const bcc = ref('');
const content = ref('');
const attachments = ref([]);

const fileInput = ref(null); // Add this for file input reference
const uploadedFiles = ref([]); // Track uploaded files
const selectedFiles = ref([]); // Track selected files before upload

const isUploading = ref(false); // Add this ref for global upload state
const uploadProgress = ref({}); // Track progress for each file

// File handling methods
function triggerFileUpload() {
    fileInput.value.click();
}

function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        selectedFiles.value.push({
            file,
            name: file.name,
            size: formatFileSize(file.size),
            uploading: true
        });
        uploadFile(file);
    });
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${Math.round(size * 100) / 100} ${units[unitIndex]}`;
}

// Modified upload function with progress
async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    uploadProgress.value[file.name] = 0;
    isUploading.value = true;

    try {
        const response = await axios.post('/api/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (e) => {
                uploadProgress.value[file.name] = Math.round((e.loaded * 100) / e.total);
            }
        });

        uploadedFiles.value.push({
            name: file.name,
            path: response.data.path,
            size: formatFileSize(file.size),
            mime_type: response.data.mime_type
        });

    } catch (error) {
        console.error('Upload failed:', error);
        selectedFiles.value = selectedFiles.value.filter(f => f.name !== file.name);
    } finally {
        delete uploadProgress.value[file.name];
        isUploading.value = false;
    }
}

function removeFile(index) {
    uploadedFiles.value.splice(index, 1);
    selectedFiles.value.splice(index, 1);
}

// Simplified send handler
const handleSend = () => {
    const formData = {
        content: content.value,
        cc: cc.value?.trim() || null,      // Ensure CC is properly formatted
        bcc: bcc.value?.trim() || null,    // Ensure BCC is properly formatted
        subject: props.subject,
        attachments: uploadedFiles.value
    };
    
    // Debug log
    console.log('Sending email data:', formData);
    
    emit('send', formData);
    resetForm();
};

function resetForm() {
    content.value = '';
    cc.value = '';
    bcc.value = [];
    attachments.value = [];
}

// Initialize signature on component mount
onMounted(() => {
    if (props.signatures?.length) {
        const defaultSig = props.signatures.find(s => s.isDefault);
        if (defaultSig) {
            selectedSignature.value = defaultSig.id;
        }
    }
});
</script>

<template>
    <div class="border rounded-lg bg-white">
        <!-- Email header -->
        <div class="p-4 border-b space-y-3">
            <!-- Subject line -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">Subject:</span>
                <input type="text" :value="subject" disabled
                       class="flex-1 bg-gray-50 text-gray-600 rounded border-gray-200" />
            </div>

            <!-- To line -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">To:</span>
                <input type="text" :value="recipient" disabled
                       class="flex-1 bg-gray-50 text-gray-600 rounded border-gray-200" />
            </div>

            <!-- CC line -->
            <div v-show="showCc || cc" class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">CC:</span>
                <input v-model="cc" 
                       type="text" 
                       :disabled="disabled"
                       class="flex-1 rounded border-gray-200" />
            </div>

            <!-- BCC line -->
            <div v-show="showBcc || bcc" class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">BCC:</span>
                <input v-model="bcc" 
                       type="text" 
                       :disabled="disabled"
                       class="flex-1 rounded border-gray-200" />
            </div>

            <!-- CC/BCC toggle buttons -->
            <div class="flex items-center gap-2 text-sm">
                <button v-if="!showCc && !cc" 
                        @click="showCc = true"
                        class="text-gray-600 hover:text-gray-900">
                    Add CC
                </button>
                <button v-if="!showBcc && !bcc" 
                        @click="showBcc = true"
                        class="text-gray-600 hover:text-gray-900">
                    Add BCC
                </button>
            </div>
        </div>

        <!-- Email content -->
        <div class="p-4">
            <textarea v-model="content"
                      class="w-full h-40 rounded border-gray-200 resize-none"
                      placeholder="Type your reply..."></textarea>

            <!-- Attachments section -->
            <div v-if="selectedFiles.length" class="mt-4 space-y-2">
                <div v-for="(file, index) in selectedFiles" 
                     :key="file.name"
                     class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex items-center gap-2 flex-1">
                        <Icon name="document" size="4" class="text-gray-400" />
                        <span class="text-sm">{{ file.name }}</span>
                        <span class="text-xs text-gray-500">({{ file.size }})</span>
                    </div>
                    
                    <!-- Progress bar -->
                    <div v-if="uploadProgress[file.name] !== undefined" 
                         class="flex-1 mx-4">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 transition-all duration-200"
                                 :style="{ width: `${uploadProgress[file.name]}%` }">
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 text-right mt-1">
                            {{ uploadProgress[file.name] }}%
                        </div>
                    </div>

                    <!-- Remove button -->
                    <button v-else
                            @click="removeFile(index)"
                            class="text-red-500 hover:text-red-700">
                        <Icon name="x" size="4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="p-4 border-t flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Hidden file input -->
                <input ref="fileInput"
                       type="file"
                       multiple
                       class="hidden"
                       @change="handleFileSelect" />

                <!-- Upload button with loading state -->
                <button @click="triggerFileUpload"
                        :disabled="isUploading"
                        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 disabled:opacity-50">
                    <Icon :name="isUploading ? 'loading' : 'paperclip'" 
                          size="4" 
                          :class="{ 'animate-spin': isUploading }" />
                    {{ isUploading ? 'Uploading...' : 'Attach' }}
                </button>
            </div>

            <!-- Send button -->
            <button @click="handleSend"
                    :disabled="disabled || loading"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700
                           inline-flex items-center gap-2 disabled:opacity-50">
                <Icon v-if="loading" name="loading" class="animate-spin" size="4" />
                <Icon v-else name="send" size="4" />
                {{ loading ? 'Sending...' : 'Send' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.signature-preview {
    opacity: 0.7;
    pointer-events: none;
    font-family: monospace;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.upload-progress {
    transition: width 0.3s ease-in-out;
}
</style>
