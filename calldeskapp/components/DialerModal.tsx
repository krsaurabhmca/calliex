import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Modal, Dimensions, Platform } from 'react-native';
import { Phone, X, Delete, PhoneCall } from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';
import { makeCall } from '../services/dialer';

const { width: screenWidth } = Dimensions.get('window');

interface DialerModalProps {
    visible: boolean;
    onClose: () => void;
}

export default function DialerModal({ visible, onClose }: DialerModalProps) {
    const [phoneNumber, setPhoneNumber] = useState('');
    const insets = useSafeAreaInsets();

    const handleNumberPress = (val: string) => {
        if (phoneNumber.length < 15) {
            Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
            setPhoneNumber(prev => prev + val);
        }
    };

    const handleDeletePress = () => {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
        setPhoneNumber(prev => prev.slice(0, -1));
    };

    const handleClearAll = () => {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
        setPhoneNumber('');
    };

    const handleCall = () => {
        if (phoneNumber) {
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            makeCall(phoneNumber);
            onClose();
        }
    };

    const keys = [
        { num: '1', sub: ' ' }, { num: '2', sub: 'ABC' }, { num: '3', sub: 'DEF' },
        { num: '4', sub: 'GHI' }, { num: '5', sub: 'JKL' }, { num: '6', sub: 'MNO' },
        { num: '7', sub: 'PQRS' }, { num: '8', sub: 'TUV' }, { num: '9', sub: 'WXYZ' },
        { num: '*', sub: ' ' }, { num: '0', sub: '+' }, { num: '#', sub: ' ' }
    ];

    const formatDisplay = (num: string) => {
        // Simple formatting for display if needed, but keeping it raw is often better for a dialer
        return num;
    };

    return (
        <Modal
            visible={visible}
            animationType="slide"
            transparent={true}
            onRequestClose={onClose}
        >
            <View style={styles.overlay}>
                <View style={[
                    styles.modalContent, 
                    { paddingBottom: Math.max(insets.bottom, 24) }
                ]}>
                    <View style={styles.dragHandle} />
                    
                    <View style={styles.header}>
                        <View style={styles.headerIconBox}>
                            <Phone size={18} color="#6366f1" strokeWidth={2.5} />
                        </View>
                        <View style={styles.headerTextWrapper}>
                            <Text style={styles.headerTitle}>Quick Dialer</Text>
                            <Text style={styles.headerSubtitle}>Ready to connect</Text>
                        </View>
                        <TouchableOpacity 
                            onPress={() => {
                                Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
                                onClose();
                            }} 
                            style={styles.closeBtn}
                        >
                            <X size={20} color="#64748b" strokeWidth={2} />
                        </TouchableOpacity>
                    </View>

                    <View style={styles.displayContainer}>
                        <View style={styles.numberWrapper}>
                            <Text 
                                style={[
                                    styles.displayText,
                                    phoneNumber.length > 10 && { fontSize: 32 }
                                ]} 
                                numberOfLines={1}
                                adjustsFontSizeToFit
                            >
                                {formatDisplay(phoneNumber) || ' '}
                            </Text>
                        </View>
                        {phoneNumber.length > 0 && (
                            <TouchableOpacity 
                                onPress={handleDeletePress} 
                                onLongPress={handleClearAll}
                                style={styles.deleteBtn}
                            >
                                <Delete color="#94a3b8" size={26} strokeWidth={1.5} />
                            </TouchableOpacity>
                        )}
                    </View>

                    <View style={styles.keypad}>
                        {keys.map((key, index) => (
                            <View key={index} style={styles.keyWrapper}>
                                <TouchableOpacity
                                    style={styles.key}
                                    activeOpacity={0.5}
                                    onPress={() => handleNumberPress(key.num)}
                                    onLongPress={() => key.num === '0' && handleNumberPress('+')}
                                >
                                    <Text style={styles.keyNum}>{key.num}</Text>
                                    {key.sub !== ' ' && <Text style={styles.keySub}>{key.sub}</Text>}
                                </TouchableOpacity>
                            </View>
                        ))}
                    </View>

                    <View style={styles.actions}>
                        <TouchableOpacity
                            style={[styles.callBtn, !phoneNumber && styles.disabledBtn]}
                            onPress={handleCall}
                            disabled={!phoneNumber}
                            activeOpacity={0.8}
                        >
                            <PhoneCall color="#fff" size={32} strokeWidth={2.5} />
                        </TouchableOpacity>
                    </View>
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    overlay: {
        flex: 1,
        backgroundColor: 'rgba(15, 23, 42, 0.4)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#ffffff',
        borderTopLeftRadius: 32,
        borderTopRightRadius: 32,
        paddingHorizontal: 28,
        paddingTop: 12,
        elevation: 25,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: -12 },
        shadowOpacity: 0.15,
        shadowRadius: 24,
    },
    dragHandle: {
        width: 36,
        height: 4,
        backgroundColor: '#e2e8f0',
        borderRadius: 2,
        alignSelf: 'center',
        marginBottom: 20,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 24,
    },
    headerIconBox: {
        width: 40,
        height: 40,
        borderRadius: 12,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    headerTextWrapper: {
        flex: 1,
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: '#0f172a',
    },
    headerSubtitle: {
        fontSize: 12,
        color: '#64748b',
        fontWeight: '500',
    },
    closeBtn: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: '#f1f5f9',
        justifyContent: 'center',
        alignItems: 'center',
    },
    displayContainer: {
        width: '100%',
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 32,
        height: 60,
    },
    numberWrapper: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    displayText: {
        fontSize: 42,
        fontWeight: '700',
        color: '#0f172a',
        textAlign: 'center',
        letterSpacing: 2,
    },
    deleteBtn: {
        position: 'absolute',
        right: 0,
        padding: 8,
    },
    keypad: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'space-between',
        paddingHorizontal: 10,
        marginBottom: 32,
    },
    keyWrapper: {
        width: '30%',
        aspectRatio: 1,
        marginBottom: 16,
        alignItems: 'center',
        justifyContent: 'center',
    },
    key: {
        width: 72,
        height: 72,
        borderRadius: 36,
        backgroundColor: '#f8fafc',
        justifyContent: 'center',
        alignItems: 'center',
        borderWidth: 1,
        borderColor: '#f1f5f9',
        ...Platform.select({
            ios: {
                shadowColor: '#000',
                shadowOffset: { width: 0, height: 2 },
                shadowOpacity: 0.05,
                shadowRadius: 4,
            },
            android: {
                elevation: 2,
            }
        })
    },
    keyNum: {
        fontSize: 28,
        fontWeight: '600',
        color: '#1e293b',
    },
    keySub: {
        fontSize: 9,
        color: '#94a3b8',
        fontWeight: '700',
        marginTop: -2,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    actions: {
        alignItems: 'center',
        marginTop: 4,
    },
    callBtn: {
        width: 80,
        height: 80,
        borderRadius: 40,
        backgroundColor: '#10b981',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 8,
        shadowColor: '#10b981',
        shadowOffset: { width: 0, height: 6 },
        shadowOpacity: 0.4,
        shadowRadius: 12,
    },
    disabledBtn: {
        backgroundColor: '#f1f5f9',
        elevation: 0,
        shadowOpacity: 0,
    }
});

