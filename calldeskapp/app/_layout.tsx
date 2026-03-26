import React, { useEffect, useRef, useState } from 'react';
import { AppState, AppStateStatus } from 'react-native';
import { Stack, useRouter } from "expo-router";
import * as Linking from 'expo-linking';
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { SnackbarProvider } from "../context/SnackbarContext";
import { registerBackgroundSync } from "../services/backgroundSync";
import { runAutoSync } from "../services/autoSync";
import { apiCall } from '../services/api';
import * as ScreenCapture from 'expo-screen-capture';
import { getUser } from '../services/auth';
import LeadActionModal from '../components/LeadActionModal';

export default function RootLayout() {
  const appState = useRef<AppStateStatus>(AppState.currentState);
  const router = useRouter();

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [modalData, setModalData] = useState<any>({
    autoNumber: '',
    initialAction: 'add',
    leadId: '',
    leadName: ''
  });

  const handleDeepLink = async (url: string | null) => {
    if (!url) return;
    console.log('Deep Link received:', url);
    
    const { path, queryParams } = Linking.parse(url);
    
    // Immediate silent sync on call end/start to ensure server has latest logs
    if (queryParams?.reason === 'call_ended' || queryParams?.reason === 'on_call') {
      const statusParam = queryParams?.reason === 'on_call' ? 'on-call' : 'online';
      runAutoSync(true, statusParam); // Force sync immediately with status update
    }

    // Path might be "" or null if root is used
    if ((queryParams?.reason === 'call_ended' || queryParams?.reason === 'on_call') && queryParams?.number) {
      let phoneNumber = queryParams.number as string;
      // Clean to 10-digit Indian number
      phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
      if (phoneNumber.length > 10) {
        phoneNumber = phoneNumber.slice(-10);
      }
      
      try {
        // Search lead by mobile
        const result = await apiCall(`leads.php?search=${phoneNumber}`, 'GET');
        
        console.log(`Deep Link: Search results for ${phoneNumber}:`, result.data?.length || 0, 'items');
        
        // Find exact match by comparing the last 10 digits accurately
        const exactMatch = result.success && result.data ? 
          result.data.find((l: any) => {
            if (!l.mobile) return false;
            // Clean both to last 10 digits for comparison
            const dbNum = l.mobile.replace(/[^0-9]/g, '').slice(-10);
            const searchNum = phoneNumber.replace(/[^0-9]/g, '').slice(-10);
            return dbNum === searchNum;
          }) : 
          null;

        if (exactMatch) {
          console.log('Deep Link: Found exact lead match:', exactMatch.name);
          setModalData({
            autoNumber: phoneNumber,
            initialAction: 'update',
            leadId: exactMatch.id.toString(),
            leadName: exactMatch.name
          });
          setModalVisible(true);
        } else {
          console.log('Deep Link: No lead match found, opening Add Lead Modal');
          setModalData({
            autoNumber: phoneNumber,
            initialAction: 'add',
            leadId: '',
            leadName: ''
          });
          setModalVisible(true);
        }
      } catch (e) {
        console.error('Error checking lead status via deep link:', e);
        // Fallback to add modal if check fails
        setModalData({
          autoNumber: phoneNumber,
          initialAction: 'add',
          leadId: '',
          leadName: ''
        });
        setModalVisible(true);
      }
    }
  };

  useEffect(() => {
    const sub = Linking.addEventListener('url', (event) => {
      handleDeepLink(event.url);
    });

    Linking.getInitialURL().then((url) => {
      if (url) handleDeepLink(url);
    });

    // Register background fetch task (15-min interval when app is in background)
    const bgTimer = setTimeout(() => {
      registerBackgroundSync();
    }, 5000);

    // Listen for app coming back to foreground from minimize/background
    const subscription = AppState.addEventListener('change', async (nextState) => {
      const prev = appState.current;
      appState.current = nextState;

      // Fire when transitioning background → active (minimize → focus)
      if ((prev === 'background' || prev === 'inactive') && nextState === 'active') {
        console.log('AppState: App returned to foreground — running silent sync');
        // Silent: no splash screen, no UI. Throttled by runAutoSync internally (5 min).
        await runAutoSync();
      }
    });

    const checkPrivacySettings = async () => {
      try {
        const user = await getUser();
        if (user && user.allow_screenshot === 0) {
          console.log('Privacy Shield: Screenshots DISABLED for this user');
          await ScreenCapture.preventScreenCaptureAsync();
        } else {
          console.log('Privacy Shield: Screenshots ALLOWED');
          await ScreenCapture.allowScreenCaptureAsync();
        }
      } catch (err) {
        console.error('Failed to set privacy settings:', err);
      }
    };

    checkPrivacySettings();

    return () => {
      clearTimeout(bgTimer);
      subscription.remove();
      sub.remove();
    };
  }, []);

  return (
    <SafeAreaProvider>
      <SnackbarProvider>
        <StatusBar style="dark" />
        <Stack screenOptions={{ headerShown: false }}>
          <Stack.Screen name="index" />
          <Stack.Screen name="(auth)/login" />
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="settings/recording" options={{ title: 'Recording Settings' }} />
        </Stack>

        <LeadActionModal 
          visible={modalVisible}
          onClose={() => setModalVisible(false)}
          autoNumber={modalData.autoNumber}
          initialAction={modalData.initialAction}
          leadId={modalData.leadId}
          leadName={modalData.leadName}
        />
      </SnackbarProvider>
    </SafeAreaProvider>
  );
}
