import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, TextInput, ScrollView, KeyboardAvoidingView, Platform, Modal, FlatList } from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { apiCall } from '../services/api';
import { ArrowLeft, User, Phone, Tag, StickyNote, Calendar as CalendarIcon, Save, Trash2, UserPlus, CheckCircle2, MapPin, Globe, LayoutList, ChevronRight, X, ChevronDown, CheckCircle } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../context/SnackbarContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Picker } from '@react-native-picker/picker';

export default function LeadActionScreen() {
    const params = useLocalSearchParams();
    const { autoNumber, leadId, leadName, autoAction: initialAction } = params;
    
    const [currentAction, setCurrentAction] = useState(initialAction);
    const [currentLeadId, setCurrentLeadId] = useState(leadId);
    const router = useRouter();
    const { showSnackbar } = useSnackbar();
    const insets = useSafeAreaInsets();

    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    // Metadata from backend
    const [metadata, setMetadata] = useState<any>({
        sources: [],
        statuses: [],
        custom_fields: []
    });

    // Form fields
    const [name, setName] = useState('');
    const [mobile, setMobile] = useState('');
    const [alternateMobile, setAlternateMobile] = useState('');
    const [status, setStatus] = useState('');
    const [remark, setRemark] = useState('');
    const [sourceId, setSourceId] = useState('');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [showDatePicker, setShowDatePicker] = useState(false);

    // Geography fields
    const [states, setStates] = useState<any[]>([]);
    const [districts, setDistricts] = useState<any[]>([]);
    const [blocks, setBlocks] = useState<any[]>([]);
    const [stateId, setStateId] = useState('');
    const [districtId, setDistrictId] = useState('');
    const [blockId, setBlockId] = useState('');

    // Selection Modal state
    const [pickerModalVisible, setPickerModalVisible] = useState(false);
    const [pickerData, setPickerData] = useState<{title: string, data: any[], onSelect: (val: any) => void, selectedId: string}>({
        title: '', data: [], onSelect: () => {}, selectedId: ''
    });

    // Custom Field values
    const [customValues, setCustomValues] = useState<any>({});
    const [isInteractionOnly, setIsInteractionOnly] = useState(false);

    const scrollViewRef = React.useRef<ScrollView>(null);

    useEffect(() => {
        const prepare = async () => {
            setLoading(true);
            
            // 1. Fetch Metadata
            const metaRes = await apiCall('leads.php?action=form_metadata', 'GET');
            if (metaRes.success) {
                setMetadata(metaRes.data);
                if (initialAction === 'add') {
                    if (metaRes.data.statuses.length > 0) setStatus(metaRes.data.statuses[0].status_name);
                    if (metaRes.data.sources.length > 0) setSourceId(metaRes.data.sources[0].id.toString());
                }
            }

            // 2. Fetch States
            const statesRes = await apiCall('geography.php?type=states', 'GET');
            if (statesRes.success) setStates(statesRes.data);

            // 3. Load Lead Data (if Update or searching mobile)
            if (currentLeadId) {
                const leadRes = await apiCall(`leads.php?id=${currentLeadId}`, 'GET');
                if (leadRes.success && leadRes.data) {
                    const l = leadRes.data;
                    setName(l.name);
                    setMobile(l.mobile);
                    setAlternateMobile(l.alternate_mobile || '');
                    setStatus(l.status);
                    setSourceId(l.source_id?.toString() || '');
                    
                    // Geography - Chain load
                    if (l.state_id) {
                        setStateId(l.state_id.toString());
                        const dRes = await apiCall(`geography.php?type=districts&state_id=${l.state_id}`, 'GET');
                        if (dRes.success) setDistricts(dRes.data);
                    }
                    if (l.district_id) {
                        setDistrictId(l.district_id.toString());
                        const bRes = await apiCall(`geography.php?type=blocks&district_id=${l.district_id}`, 'GET');
                        if (bRes.success) setBlocks(bRes.data);
                    }
                    if (l.block_id) setBlockId(l.block_id.toString());
                    
                    // Custom Values
                    if (l.custom_values) setCustomValues(l.custom_values);
                }
            } else if (autoNumber) {
                setMobile(autoNumber as string);
                const searchRes = await apiCall(`leads.php?search=${autoNumber}`, 'GET');
                if (searchRes.success && searchRes.data && searchRes.data.length > 0) {
                    const l = searchRes.data[0];
                    setCurrentAction('update');
                    setCurrentLeadId(l.id.toString());
                    setName(l.name);
                    setAlternateMobile(l.alternate_mobile || '');
                    setStatus(l.status);
                    setSourceId(l.source_id?.toString() || '');
                }
            }
            
            setLoading(false);

            // 4. Auto-scroll to interaction if requested
            if (params.focusInteraction === 'true') {
                setIsInteractionOnly(true);
                setTimeout(() => {
                    scrollViewRef.current?.scrollToEnd({ animated: true });
                }, 800);
            }
        };
        prepare();
    }, [currentLeadId, autoNumber]);

    // Geography Handlers
    const handleStateChange = async (id: string) => {
        setStateId(id);
        setDistrictId('');
        setBlockId('');
        setDistricts([]);
        setBlocks([]);
        if (!id) return;
        const res = await apiCall(`geography.php?type=districts&state_id=${id}`, 'GET');
        if (res.success) setDistricts(res.data);
    };

    const handleDistrictChange = async (id: string) => {
        setDistrictId(id);
        setBlockId('');
        setBlocks([]);
        if (!id) return;
        const res = await apiCall(`geography.php?type=blocks&district_id=${id}`, 'GET');
        if (res.success) setBlocks(res.data);
    };

    // Selection Modal Trigger
    const openPicker = (title: string, data: any[], selectedId: string, onSelect: (val: any) => void) => {
        setPickerData({ title, data, onSelect, selectedId });
        setPickerModalVisible(true);
    };

    const handleSave = async () => {
        if (!name) {
            showSnackbar('Please enter lead name', 'error');
            return;
        }

        setSubmitting(true);
        try {
            if (currentAction === 'add') {
                const result = await apiCall('leads.php', 'POST', {
                    name,
                    mobile,
                    alternate_mobile: alternateMobile,
                    source_id: sourceId,
                    status,
                    remarks: remark || 'Lead created via Mobile App',
                    state_id: stateId,
                    district_id: districtId,
                    block_id: blockId,
                    custom_fields: JSON.stringify(customValues)
                });
                if (result.success) {
                    showSnackbar('Lead Created ✨', 'success');
                    router.replace('/(tabs)/leads');
                } else {
                    showSnackbar(result.message || 'Creation failed', 'error');
                }
            } else {
                // Update Basic Lead Info + Custom Fields
                const result = await apiCall('leads.php', 'PUT', {
                    id: currentLeadId,
                    name,
                    alternate_mobile: alternateMobile,
                    source_id: sourceId,
                    status,
                    state_id: stateId,
                    district_id: districtId,
                    block_id: blockId,
                    custom_fields: JSON.stringify(customValues)
                });
                
                // Also add an Interaction Record (Follow-up)
                if (remark) {
                    await apiCall('followups.php', 'POST', {
                        lead_id: currentLeadId,
                        status,
                        remark,
                        next_follow_up_date: nextFollowUp,
                    });
                }

                if (result.success) {
                    showSnackbar('Profile Updated ✅', 'success');
                    router.replace('/(tabs)/leads');
                } else {
                    showSnackbar(result.message || 'Update failed', 'error');
                }
            }
        } catch (e) {
            showSnackbar('Network Error', 'error');
        } finally {
            setSubmitting(false);
        }
    };

    const renderCustomField = (f: any) => {
        if (f.field_type === 'AUTO') return null;
        
        const label = `${f.field_name} ${f.is_mandatory == 1 ? '*' : ''}`;

        if (f.field_type === 'OPTION') {
            const options = (f.field_options || '').split(',').map((o: string) => o.trim()).filter((o: string) => o);
            const currentVal = customValues[f.id] || '';
            
            return (
                <View key={f.id} style={styles.inputGroup}>
                    <Text style={styles.label}>{label}</Text>
                    <TouchableOpacity 
                        style={styles.selector} 
                        onPress={() => openPicker(f.field_name, options.map((o:any) => ({id: o, name: o})), currentVal, (v) => setCustomValues({...customValues, [f.id]: v}))}
                    >
                        <LayoutList size={18} color="#6366f1" />
                        <Text style={[styles.selectorText, !currentVal && {color: '#94a3b8'}]}>{currentVal || 'Select Option'}</Text>
                        <ChevronDown size={16} color="#94a3b8" />
                    </TouchableOpacity>
                </View>
            );
        }

        return (
            <View key={f.id} style={styles.inputGroup}>
                <Text style={styles.label}>{label}</Text>
                <View style={styles.inputContainer}>
                    <LayoutList size={18} color="#94a3b8" />
                    <TextInput 
                        style={styles.input} 
                        placeholder={`Enter ${f.field_name}`}
                        value={customValues[f.id] || ''}
                        onChangeText={(v) => setCustomValues({...customValues, [f.id]: v})}
                        keyboardType={f.field_type === 'NUMBER' ? 'numeric' : 'default'}
                        placeholderTextColor="#cbd5e1"
                    />
                    {customValues[f.id] ? <CheckCircle2 size={14} color="#10b981" /> : null}
                </View>
            </View>
        );
    };

    if (loading) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
                <Text style={{ marginTop: 12, color: '#94a3b8', fontWeight: '800', fontSize: 13 }}>ORCHESTRATING DATA...</Text>
            </View>
        );
    }

    return (
        <View style={[styles.container, { paddingTop: insets.top, paddingBottom: insets.bottom }]}>
            <Stack.Screen options={{ headerShown: false }} />
            <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
                
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => router.back()} style={styles.backBtn} activeOpacity={0.7}>
                        <ArrowLeft size={22} color="#1e293b" />
                    </TouchableOpacity>
                    <View>
                        <Text style={styles.headerBadge}>{currentAction === 'add' ? 'INITIAL SETUP' : 'PROFILE EDIT'}</Text>
                        <Text style={styles.headerTitle}>{currentAction === 'add' ? 'New Prospect' : leadName || 'Update Lead'}</Text>
                    </View>
                </View>
                <ScrollView ref={scrollViewRef} style={styles.content} showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 40 }}>
                    
                    {currentAction === 'update' && isInteractionOnly ? (
                        <View style={{ marginTop: 10 }}>
                            <View style={[styles.card, { backgroundColor: '#fff', borderColor: '#f1f5f9', borderWidth: 1, padding: 20 }]}>
                                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 20 }}>
                                    <View style={{ width: 44, height: 44, borderRadius: 14, backgroundColor: '#6366f1', justifyContent: 'center', alignItems: 'center' }}>
                                        <Text style={{ color: '#fff', fontSize: 18, fontWeight: '900' }}>{name.charAt(0).toUpperCase()}</Text>
                                    </View>
                                    <View>
                                        <Text style={{ fontSize: 18, fontWeight: '900', color: '#1e293b' }}>{name}</Text>
                                        <Text style={{ fontSize: 12, fontWeight: '700', color: '#6366f1' }}>{mobile}</Text>
                                    </View>
                                </View>

                                <View style={{ height: 1.5, backgroundColor: '#f1f5f9', marginBottom: 20 }} />

                                <Text style={styles.label}>Updated Status</Text>
                                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 20 }}>
                                    {metadata.statuses.map((s: any) => (
                                        <TouchableOpacity 
                                            key={s.id} 
                                            onPress={() => setStatus(s.status_name)} 
                                            style={[styles.statusToggle, status === s.status_name && { backgroundColor: s.color_code || '#6366f1', borderColor: s.color_code || '#6366f1' }]}
                                        >
                                            <Text style={[styles.statusToggleText, status === s.status_name && { color: '#fff' }]}>{s.status_name}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </ScrollView>

                                <Text style={styles.label}>Activity Remark</Text>
                                <View style={[styles.inputContainer, { alignItems: 'flex-start', paddingTop: 14, minHeight: 120, backgroundColor: '#f8fafc' }]}>
                                    <TextInput 
                                        style={[styles.input, { height: 100, textAlignVertical: 'top' }]} 
                                        value={remark} 
                                        onChangeText={setRemark} 
                                        placeholder="What happened on this call?" 
                                        multiline 
                                        numberOfLines={5}
                                        placeholderTextColor="#cbd5e1"
                                        autoFocus={true}
                                    />
                                </View>

                                <View style={{ marginTop: 20 }}>
                                    <Text style={styles.label}>Next Follow Up</Text>
                                    <TouchableOpacity style={[styles.dateSelector, { backgroundColor: '#f8fafc' }]} onPress={() => setShowDatePicker(true)}>
                                        <CalendarIcon size={18} color="#6366f1" />
                                        <Text style={styles.dateSelectorText}>{nextFollowUp || 'Choose date'}</Text>
                                        <ChevronDown size={16} color="#94a3b8" />
                                    </TouchableOpacity>
                                </View>
                                
                                {showDatePicker && (
                                    <DateTimePicker value={nextFollowUp ? new Date(nextFollowUp) : new Date()} mode="date" display="default" minimumDate={new Date()}
                                        onChange={(e, d) => { setShowDatePicker(false); if (d) setNextFollowUp(d.toISOString().split('T')[0]); }} />
                                )}
                            </View>

                            <TouchableOpacity 
                                style={[styles.mainBtn, submitting && { opacity: 0.7 }, { marginTop: 10 }]} 
                                onPress={handleSave} 
                                disabled={submitting}
                                activeOpacity={0.8}
                            >
                                {submitting ? (
                                    <ActivityIndicator color="#fff" />
                                ) : (
                                    <>
                                        <CheckCircle size={20} color="#fff" />
                                        <Text style={styles.mainBtnText}>STAMP ACTIVITY</Text>
                                    </>
                                )}
                            </TouchableOpacity>

                            <TouchableOpacity onPress={() => setIsInteractionOnly(false)} style={{ marginTop: 30, alignSelf: 'center' }}>
                                <Text style={{ fontSize: 13, fontWeight: '800', color: '#94a3b8', textDecorationLine: 'underline' }}>EDIT FULL PROFILE</Text>
                            </TouchableOpacity>
                        </View>
                    ) : (
                        <>
                            {/* Identification */}
                            <View style={styles.card}>
                                <View style={styles.cardHeader}>
                                    <User size={18} color="#6366f1" />
                                    <Text style={styles.cardTitle}>Identity Portfolio</Text>
                                </View>
                                
                                <View style={styles.inputGroup}>
                                    <Text style={styles.label}>Full Name *</Text>
                                    <View style={styles.inputContainer}>
                                        <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Customer Name" placeholderTextColor="#cbd5e1" />
                                    </View>
                                </View>

                                <View style={styles.row}>
                                    <View style={[styles.inputGroup, { flex: 1 }]}>
                                        <Text style={styles.label}>Primary Mobile</Text>
                                        <View style={[styles.inputContainer, styles.disabledInput]}>
                                            <TextInput 
                                                style={styles.input} 
                                                value={metadata.user?.mask_numbers === 1 && metadata.user?.role !== 'admin' ? (mobile.length > 5 ? mobile.substring(0, mobile.length - 5) + 'XXXXX' : mobile) : mobile} 
                                                editable={false} 
                                                placeholderTextColor="#94a3b8"
                                            />
                                        </View>
                                    </View>
                                    <View style={{ width: 12 }} />
                                    <View style={[styles.inputGroup, { flex: 1 }]}>
                                        <Text style={styles.label}>Alternate / WhatsApp</Text>
                                        <View style={styles.inputContainer}>
                                            <TextInput 
                                                style={styles.input} 
                                                value={metadata.user?.mask_numbers === 1 && metadata.user?.role !== 'admin' && alternateMobile.length > 5 ? alternateMobile.substring(0, alternateMobile.length - 5) + 'XXXXX' : alternateMobile} 
                                                onChangeText={setAlternateMobile} 
                                                placeholder="Optional" 
                                                keyboardType="phone-pad" 
                                                placeholderTextColor="#cbd5e1" 
                                            />
                                        </View>
                                    </View>
                                </View>
                            </View>

                            {/* Geography Selection - UPGRADED UI */}
                            <View style={styles.card}>
                                <View style={styles.cardHeader}>
                                    <Globe size={18} color="#6366f1" />
                                    <Text style={styles.cardTitle}>Geographical Context</Text>
                                </View>
                                
                                <View style={styles.geoRow}>
                                    <TouchableOpacity 
                                        style={[styles.geoPill, !stateId && styles.geoPillEmpty]} 
                                        onPress={() => openPicker('Select State', states, stateId, handleStateChange)}
                                    >
                                        <Text style={styles.geoLabel}>STATE</Text>
                                        <Text style={styles.geoVal} numberOfLines={1}>
                                            {states.find(s => s.id.toString() === stateId)?.name || 'Choose...'}
                                        </Text>
                                    </TouchableOpacity>

                                    <TouchableOpacity 
                                        style={[styles.geoPill, !districtId && styles.geoPillEmpty, !stateId && styles.geoPillDisabled]} 
                                        disabled={!stateId}
                                        onPress={() => openPicker('Select District', districts, districtId, handleDistrictChange)}
                                    >
                                        <Text style={styles.geoLabel}>DISTRICT</Text>
                                        <Text style={styles.geoVal} numberOfLines={1}>
                                            {districts.find(d => d.id.toString() === districtId)?.name || 'Choose...'}
                                        </Text>
                                    </TouchableOpacity>
                                </View>

                                <TouchableOpacity 
                                    style={[styles.geoPillFull, !blockId && styles.geoPillEmpty, !districtId && styles.geoPillDisabled, {marginTop: 12}]} 
                                    disabled={!districtId}
                                    onPress={() => openPicker('Select Block', blocks, blockId, setBlockId)}
                                >
                                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <View>
                                            <Text style={styles.geoLabel}>WARD / BLOCK / AREA</Text>
                                            <Text style={styles.geoVal}>
                                                {blocks.find(b => b.id.toString() === blockId)?.name || 'Select local area'}
                                            </Text>
                                        </View>
                                        <ChevronRight size={20} color="#cbd5e1" />
                                    </View>
                                </TouchableOpacity>
                            </View>

                            {/* Classification */}
                            <View style={styles.card}>
                                <View style={styles.cardHeader}>
                                    <Tag size={18} color="#6366f1" />
                                    <Text style={styles.cardTitle}>Sales Categorization</Text>
                                </View>
                                
                                <Text style={styles.label}>Prospect Stage</Text>
                                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 16 }}>
                                    {metadata.statuses.map((s: any) => (
                                        <TouchableOpacity 
                                            key={s.id} 
                                            onPress={() => setStatus(s.status_name)} 
                                            style={[styles.statusToggle, status === s.status_name && { backgroundColor: s.color_code || '#6366f1', borderColor: s.color_code || '#6366f1' }]}
                                        >
                                            <Text style={[styles.statusToggleText, status === s.status_name && { color: '#fff' }]}>{s.status_name}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </ScrollView>

                                <Text style={styles.label}>Lead Source</Text>
                                <View style={styles.sourceGrid}>
                                    {metadata.sources.map((s: any) => (
                                        <TouchableOpacity 
                                            key={s.id} 
                                            onPress={() => setSourceId(s.id.toString())} 
                                            style={[styles.sourcePill, sourceId === s.id.toString() && styles.sourcePillActive]}
                                        >
                                            <Text style={[styles.sourcePillText, sourceId === s.id.toString() && styles.sourcePillTextActive]}>{s.source_name}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>

                            {/* Dynamic Custom Fields */}
                            {metadata.custom_fields.filter((f:any) => f.field_type !== 'AUTO').length > 0 && (
                                <View style={styles.card}>
                                    <View style={styles.cardHeader}>
                                        <LayoutList size={18} color="#6366f1" />
                                        <Text style={styles.cardTitle}>Extended Profiling</Text>
                                    </View>
                                    {metadata.custom_fields.map(renderCustomField)}
                                </View>
                            )}

                            {/* Interactions */}
                            <View style={styles.card}>
                                <View style={styles.cardHeader}>
                                    <StickyNote size={18} color="#6366f1" />
                                    <Text style={styles.cardTitle}>Activity Logging</Text>
                                </View>
                                
                                <View style={[styles.inputContainer, { alignItems: 'flex-start', paddingTop: 14, minHeight: 100 }]}>
                                    <TextInput 
                                        style={[styles.input, { height: 80, textAlignVertical: 'top' }]} 
                                        value={remark} 
                                        onChangeText={setRemark} 
                                        placeholder="Capture meeting notes, requirements, or reason for status change..." 
                                        multiline 
                                        numberOfLines={4}
                                        placeholderTextColor="#cbd5e1"
                                    />
                                </View>

                                <View style={{ marginTop: 16 }}>
                                    <Text style={styles.label}>Schedule Next Touchpoint</Text>
                                    <TouchableOpacity style={styles.dateSelector} onPress={() => setShowDatePicker(true)}>
                                        <CalendarIcon size={18} color="#6366f1" />
                                        <Text style={styles.dateSelectorText}>{nextFollowUp || 'Set reminder date'}</Text>
                                        <ChevronDown size={16} color="#94a3b8" />
                                    </TouchableOpacity>
                                </View>
                                
                                {showDatePicker && (
                                    <DateTimePicker value={nextFollowUp ? new Date(nextFollowUp) : new Date()} mode="date" display="default" minimumDate={new Date()}
                                        onChange={(e, d) => { setShowDatePicker(false); if (d) setNextFollowUp(d.toISOString().split('T')[0]); }} />
                                )}
                            </View>

                            <TouchableOpacity 
                                style={[styles.mainBtn, submitting && { opacity: 0.7 }]} 
                                onPress={handleSave} 
                                disabled={submitting}
                                activeOpacity={0.8}
                            >
                                {submitting ? (
                                    <ActivityIndicator color="#fff" />
                                ) : (
                                    <>
                                        <Save size={20} color="#fff" />
                                        <Text style={styles.mainBtnText}>{currentAction === 'add' ? 'COMMIT NEW LEAD' : 'COMMIT UPDATES'}</Text>
                                    </>
                                )}
                            </TouchableOpacity>
                        </>
                    )}
                </ScrollView>
            </KeyboardAvoidingView>

            {/* UPGRADED PICKER MODAL */}
            <Modal visible={pickerModalVisible} transparent animationType="slide">
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>{pickerData.title}</Text>
                            <TouchableOpacity onPress={() => setPickerModalVisible(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        <FlatList
                            data={pickerData.data}
                            keyExtractor={(item) => item.id?.toString() || item.toString()}
                            renderItem={({ item }) => {
                                const id = item.id?.toString() || item.toString();
                                const name = item.name || item.toString();
                                const isSelected = String(pickerData.selectedId) === String(id);
                                return (
                                    <TouchableOpacity 
                                        style={[styles.modalItem, isSelected && styles.modalItemSelected]} 
                                        onPress={() => { pickerData.onSelect(id); setPickerModalVisible(false); }}
                                    >
                                        <Text style={[styles.modalItemText, isSelected && styles.modalItemSelectedText]}>{name}</Text>
                                        {isSelected && <CheckCircle size={18} color="#6366f1" />}
                                    </TouchableOpacity>
                                );
                            }}
                            contentContainerStyle={{ padding: 16 }}
                        />
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#fcfcfe' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    backBtn: { width: 44, height: 44, borderRadius: 18, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
    headerBadge: { fontSize: 10, fontWeight: '900', color: '#6366f1', letterSpacing: 0.5, marginBottom: 2 },
    headerTitle: { fontSize: 20, fontWeight: '900', color: '#0f172a' },
    content: { flex: 1, padding: 16 },
    card: { backgroundColor: '#fff', borderRadius: 24, padding: 18, marginBottom: 16, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.03, shadowRadius: 10, elevation: 2 },
    cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 18 },
    cardTitle: { fontSize: 14, fontWeight: '900', color: '#1e293b', textTransform: 'uppercase', letterSpacing: 1 },
    row: { flexDirection: 'row' },
    inputGroup: { marginBottom: 16 },
    label: { fontSize: 11, fontWeight: '800', color: '#94a3b8', marginBottom: 8, textTransform: 'uppercase', letterSpacing: 0.5 },
    inputContainer: { flexDirection: 'row', backgroundColor: '#f8fafc', borderRadius: 16, paddingHorizontal: 16, borderWidth: 1.5, borderColor: '#f1f5f9', overflow: 'hidden' },
    disabledInput: { backgroundColor: '#f1f5f9' },
    input: { flex: 1, height: 54, fontSize: 15, color: '#1e293b', fontWeight: '700' },
    geoRow: { flexDirection: 'row', gap: 12 },
    geoPill: { flex: 1, backgroundColor: '#eef2ff', padding: 12, borderRadius: 16, borderWidth: 1.5, borderColor: '#e0e7ff' },
    geoPillFull: { width: '100%', backgroundColor: '#eef2ff', padding: 14, borderRadius: 16, borderWidth: 1.5, borderColor: '#e0e7ff' },
    geoPillEmpty: { backgroundColor: '#fff', borderStyle: 'dashed', borderColor: '#cbd5e1' },
    geoPillDisabled: { opacity: 0.5 },
    geoLabel: { fontSize: 9, fontWeight: '900', color: '#6366f1', marginBottom: 4 },
    geoVal: { fontSize: 14, fontWeight: '800', color: '#1e293b' },
    statusToggle: { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 14, backgroundColor: '#f1f5f9', marginRight: 10, borderWidth: 1.5, borderColor: '#f1f5f9' },
    statusToggleText: { fontSize: 13, fontWeight: '800', color: '#64748b' },
    sourceGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
    sourcePill: { paddingHorizontal: 14, paddingVertical: 10, borderRadius: 14, backgroundColor: '#fff', borderWidth: 1.5, borderColor: '#f1f5f9' },
    sourcePillActive: { borderColor: '#6366f1', backgroundColor: '#f5f3ff' },
    sourcePillText: { fontSize: 12, fontWeight: '700', color: '#64748b' },
    sourcePillTextActive: { color: '#6366f1' },
    selector: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f5f3ff', borderRadius: 16, paddingHorizontal: 16, height: 54, borderWidth: 1.5, borderColor: '#e0e7ff' },
    selectorText: { flex: 1, marginLeft: 12, fontSize: 15, fontWeight: '700', color: '#1e293b' },
    dateSelector: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f8fafc', borderRadius: 16, paddingHorizontal: 16, height: 54, borderWidth: 1.5, borderColor: '#f1f5f9' },
    dateSelectorText: { flex: 1, marginLeft: 12, fontSize: 15, fontWeight: '700', color: '#1e293b' },
    mainBtn: { backgroundColor: '#6366f1', height: 64, borderRadius: 20, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 12, shadowColor: '#6366f1', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.3, shadowRadius: 15, elevation: 6 },
    mainBtnText: { color: '#fff', fontSize: 17, fontWeight: '900' },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: '#fff', borderTopLeftRadius: 32, borderTopRightRadius: 32, maxHeight: '80%' },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 24, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    modalTitle: { fontSize: 18, fontWeight: '900', color: '#0f172a' },
    modalItem: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 18, paddingHorizontal: 8, borderBottomWidth: 1, borderBottomColor: '#f8fafc' },
    modalItemSelected: { backgroundColor: '#f5f3ff', borderRadius: 12, paddingHorizontal: 16 },
    modalItemText: { fontSize: 16, fontWeight: '700', color: '#475569' },
    modalItemSelectedText: { color: '#6366f1', fontWeight: '800' }
});
