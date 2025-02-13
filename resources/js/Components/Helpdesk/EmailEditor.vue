<script setup>
import { ref, computed, watch } from 'vue';
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
    signatures: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['send']);

const showCc = ref(false);
const showBcc = ref(false);
const cc = ref('');
const bcc = ref('');
const content = ref('');
const selectedSignature = ref('');
const attachments = ref([]);

// Computed property for final content with signature
const finalContent = computed(() => {
    const signature = props.signatures.find(s => s.id === selectedSignature.value)?.content || '';
    if (!signature) return content.value;
    
    return `${content.value}\n\n${getSignatureDivider()}\n${signature}`;
});

function getSignatureDivider() {
    return '-- \n'; // Standard email signature delimiter
}

function handleSend() {
    emit('send', {
        content: finalContent.value,
        cc: cc.value,
        bcc: bcc.value,
        attachments: attachments.value,
        signature: selectedSignature.value
    });
    resetForm();
}

function resetForm() {
    content.value = '';
    cc.value = '';
    bcc.value = '';
    attachments.value = [];
    selectedSignature.value = '';
}

// Auto-select default signature on mount
watch(() => props.signatures, (newSigs) => {
    if (newSigs?.length) {
        const defaultSig = newSigs.find(s => s.isDefault);
        if (defaultSig) {
            selectedSignature.value = defaultSig.id;
        }
    }
}, { immediate: true });
</script>

<template>
    <div class="border rounded-lg bg-white">
        <!-- Email header -->
        <div class="p-4 border-b space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">To:</span>
                <input type="text" :value="recipient" disabled
                       class="flex-1 bg-gray-50 text-gray-600 rounded border-gray-200" />
            </div>

            <div v-if="showCc" class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">CC:</span>
                <input v-model="cc" type="text" class="flex-1 rounded border-gray-200" />
            </div>

            <div v-if="showBcc" class="flex items-center gap-2">
                <span class="text-sm text-gray-600 w-16">BCC:</span>
                <input v-model="bcc" type="text" class="flex-1 rounded border-gray-200" />
            </div>

            <div class="flex items-center gap-2 text-sm">
                <button v-if="!showCc" 
                        @click="showCc = true"
                        class="text-gray-600 hover:text-gray-900">Add CC</button>
                <button v-if="!showBcc" 
                        @click="showBcc = true"
                        class="text-gray-600 hover:text-gray-900">Add BCC</button>
            </div>
        </div>

        <!-- Email content -->
        <div class="p-4">
            <textarea v-model="content"
                      class="w-full h-40 rounded border-gray-200 resize-none"
                      placeholder="Type your reply..."></textarea>

            <!-- Signature Preview (if selected) -->
            <div v-if="selectedSignature" 
                 class="mt-2 p-3 bg-gray-50 rounded-lg border text-sm text-gray-600">
                <div class="text-xs text-gray-400 mb-2">Signature:</div>
                <div class="border-t border-gray-200 pt-2">
                    <div v-html="props.signatures.find(s => s.id === selectedSignature)?.content"></div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="p-4 border-t flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Attachment button -->
                <button class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <Icon name="paperclip" size="4" />
                    Attach
                </button>

                <!-- Signature selector -->
                <select v-if="signatures.length" 
                        v-model="selectedSignature"
                        class="text-sm border-gray-200 rounded">
                    <option value="">No signature</option>
                    <option v-for="sig in signatures" 
                            :key="sig.id" 
                            :value="sig.id">
                        {{ sig.name }}
                    </option>
                </select>
            </div>

            <!-- Send button -->
            <button @click="handleSend"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700
                           inline-flex items-center gap-2">
                <Icon name="send" size="4" />
                Send
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
</style>
